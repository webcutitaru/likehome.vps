<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Pages\PropertyCalendar;
use App\Legacy\LegacyBridge;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyPricingPeriod;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PDO;
use Throwable;

final class AdminCalendarService
{
    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Request $request): array
    {
        LegacyBridge::boot();

        $fromGet = trim((string) $request->query('from', ''));
        $days = (int) $request->query('days', 60);
        if ($days < 30) {
            $days = 30;
        }
        if ($days > 120) {
            $days = 120;
        }

        $fromDt = DateTimeImmutable::createFromFormat('Y-m-d', $fromGet);
        if (! $fromDt || $fromDt->format('Y-m-d') !== $fromGet) {
            $fromDt = new DateTimeImmutable('today');
        }
        $fromYmd = $fromDt->format('Y-m-d');

        $rangeEndEx = $fromDt->modify('+'.$days.' days')->format('Y-m-d');

        $dates = [];
        $d = $fromDt;
        $endExclusive = DateTimeImmutable::createFromFormat('Y-m-d', $rangeEndEx);
        if (! $endExclusive) {
            $endExclusive = $fromDt->modify('+60 days');
        }
        while ($d < $endExclusive) {
            $dates[] = $d->format('Y-m-d');
            $d = $d->modify('+1 day');
        }
        $dayCount = count($dates);
        if ($dayCount === 0) {
            $dates[] = $fromYmd;
            $dayCount = 1;
        }

        $prevFrom = $fromDt->modify('-'.$dayCount.' days')->format('Y-m-d');
        $nextFrom = $fromDt->modify('+'.$dayCount.' days')->format('Y-m-d');
        $todayYmd = (new DateTimeImmutable('today'))->format('Y-m-d');

        $flashOk = '';
        $flashErr = '';
        $flashOkGet = (string) $request->query('flash_ok', '');
        $flashErrGet = (string) $request->query('flash_err', '');
        if ($flashOkGet === '1') {
            $flashOk = 'Prețul special a fost salvat.';
        } elseif ($flashOkGet === 'booking_cancelled') {
            $flashOk = 'Rezervarea a fost anulată.';
        } elseif ($flashOkGet === 'booking_updated') {
            $flashOk = 'Rezervarea a fost actualizată.';
        }
        if ($flashErrGet !== '') {
            $flashErr = $flashErrGet;
        }
        if ($flashErr !== '') {
            $flashOk = '';
        }

        $properties = Property::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get([
                'id', 'title', 'lot_id', 'slug', 'price', 'price_weekend',
                'address', 'city', 'district', 'is_active', 'min_stay',
            ])
            ->map(fn (Property $p): array => $p->toArray())
            ->all();

        $bookingRows = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_out', '>', $fromYmd)
            ->where('check_in', '<', $rangeEndEx)
            ->get([
                'id', 'property_id', 'guest_name', 'guest_email', 'guest_phone',
                'check_in', 'check_out', 'status', 'total_price', 'guests',
                'coupon_code', 'coupon_discount_amount',
                'payment_method', 'payment_status', 'payment_amount', 'refunded_amount', 'paid_at',
                'maib_checkout_id', 'maib_payment_id', 'maib_refund_id',
            ])
            ->map(function (Booking $b): array {
                $row = $b->toArray();
                $row['check_in'] = self::toYmd($b->check_in);
                $row['check_out'] = self::toYmd($b->check_out);

                return $row;
            })
            ->all();

        $blockedRows = BlockedDate::query()
            ->where('end_date', '>', $fromYmd)
            ->where('start_date', '<', $rangeEndEx)
            ->get(['property_id', 'start_date', 'end_date', 'source', 'notes'])
            ->map(fn (BlockedDate $b): array => [
                'property_id' => $b->property_id,
                'start_date' => self::toYmd($b->start_date),
                'end_date' => self::toYmd($b->end_date),
                'source' => $b->source,
                'notes' => $b->notes,
            ])
            ->all();

        $periodRows = PropertyPricingPeriod::query()
            ->where('date_end', '>', $fromYmd)
            ->where('date_start', '<', $rangeEndEx)
            ->get(['property_id', 'date_start', 'date_end', 'price', 'price_weekend', 'label', 'min_stay'])
            ->map(fn (PropertyPricingPeriod $p): array => [
                'property_id' => $p->property_id,
                'date_start' => self::toYmd($p->date_start),
                'date_end' => self::toYmd($p->date_end),
                'price' => $p->price,
                'price_weekend' => $p->price_weekend,
                'label' => $p->label,
                'min_stay' => $p->min_stay,
            ])
            ->all();

        $bookingsByProperty = [];
        foreach ($bookingRows as $br) {
            $pid = (int) $br['property_id'];
            $bookingsByProperty[$pid][] = $br;
        }

        $blocksByProperty = [];
        foreach ($blockedRows as $br) {
            $pid = (int) $br['property_id'];
            $blocksByProperty[$pid][] = $br;
        }

        $periodsByProperty = [];
        foreach ($periodRows as $pr) {
            $pid = (int) $pr['property_id'];
            $periodsByProperty[$pid][] = $pr;
        }

        $roMonths = [
            1 => 'Ian', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mai', 6 => 'Iun',
            7 => 'Iul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Noi', 12 => 'Dec',
        ];
        $roDow = ['Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm'];

        $monthSpans = [];
        if ($dayCount > 0) {
            $i = 0;
            while ($i < $dayCount) {
                $d0 = new DateTimeImmutable($dates[$i].' 12:00:00');
                $m = (int) $d0->format('n');
                $span = 1;
                $j = $i + 1;
                while ($j < $dayCount) {
                    $dj = new DateTimeImmutable($dates[$j].' 12:00:00');
                    if ((int) $dj->format('n') !== $m) {
                        break;
                    }
                    $span++;
                    $j++;
                }
                $monthSpans[] = ['label' => $roMonths[$m] ?? $d0->format('M'), 'span' => $span];
                $i = $j;
            }
        }
        $todayIdx = array_search($todayYmd, $dates, true);
        $calScrollTodayIdx = $todayIdx === false ? -1 : (int) $todayIdx;

        return compact(
            'fromYmd', 'dayCount', 'dates', 'prevFrom', 'nextFrom', 'todayYmd',
            'flashOk', 'flashErr', 'properties', 'bookingsByProperty', 'blocksByProperty',
            'periodsByProperty', 'roMonths', 'roDow', 'monthSpans', 'todayIdx', 'calScrollTodayIdx',
        );
    }

    public function handlePost(Request $request): RedirectResponse
    {
        LegacyBridge::boot();
        LegacyBridge::syncRequest($request);

        $pdo = LegacyBridge::pdo();
        $action = (string) $request->input('calendar_action', '');

        $fromRedirect = trim((string) $request->input('redirect_from', ''));
        $daysRedirect = (int) $request->input('redirect_days', 60);
        $qs = 'from='.rawurlencode($fromRedirect !== '' ? $fromRedirect : date('Y-m-d'))
            .'&days='.max(1, min(120, $daysRedirect));
        $redirectTo = PropertyCalendar::getUrl().'?'.$qs;

        if ($action === 'special_price') {
            return $this->handleSpecialPrice($request, $pdo, $redirectTo, $qs);
        }

        if ($action === 'booking_cancel') {
            return $this->handleBookingCancel($request, $pdo, $redirectTo);
        }

        if ($action === 'booking_update') {
            return $this->handleBookingUpdate($request, $pdo, $redirectTo);
        }

        return redirect()->to($redirectTo);
    }

    private function handleSpecialPrice(Request $request, PDO $pdo, string $redirectTo, string $qs): RedirectResponse
    {
        if (! lh_csrf_verify_post()) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Sesiune invalidă. Reîncarcă pagina și încearcă din nou.'));
        }

        $propertyId = (int) $request->input('property_id', 0);
        $rangeStart = trim((string) $request->input('range_start', ''));
        $rangeEndEx = trim((string) $request->input('range_end_exclusive', ''));
        $priceRaw = trim((string) $request->input('price', ''));
        $pwRaw = trim((string) $request->input('price_weekend', ''));
        $msRaw = trim((string) $request->input('min_stay', ''));
        $price = (float) str_replace(',', '.', $priceRaw);
        $pw = $pwRaw === '' ? null : (float) str_replace(',', '.', $pwRaw);
        $newMinStay = null;
        if ($msRaw !== '') {
            $msi = (int) $msRaw;
            if ($msi >= 1) {
                $newMinStay = $msi;
            }
        }

        if ($propertyId < 1) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Proprietate invalidă.'));
        }

        $d0 = DateTimeImmutable::createFromFormat('Y-m-d', $rangeStart);
        $d1 = DateTimeImmutable::createFromFormat('Y-m-d', $rangeEndEx);
        if (! $d0 || $d0->format('Y-m-d') !== $rangeStart || ! $d1 || $d1->format('Y-m-d') !== $rangeEndEx || $d1 <= $d0) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Intervalul selectat este invalid.'));
        }

        $stmtP = $pdo->prepare('SELECT id FROM properties WHERE id = ? LIMIT 1');
        $stmtP->execute([$propertyId]);
        if (! $stmtP->fetch()) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Proprietatea nu există.'));
        }

        $cur = $d0;
        while ($cur < $d1) {
            $ymd = $cur->format('Y-m-d');
            $blk = $pdo->prepare(
                'SELECT COUNT(*) FROM blocked_dates
                 WHERE property_id = ? AND start_date <= ? AND end_date > ?'
            );
            $blk->execute([$propertyId, $ymd, $ymd]);
            if ((int) $blk->fetchColumn() > 0) {
                return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Intervalul include date blocate (rezervare sau calendar extern). Alege doar zile libere.'));
            }
            $cur = $cur->modify('+1 day');
        }

        $existing = lh_property_pricing_periods_load($propertyId);
        $merged = lh_property_pricing_periods_merge_apply_range(
            $existing,
            $rangeStart,
            $rangeEndEx,
            $price,
            $pw,
            LH_PRICING_PERIOD_LABEL_CALENDAR_SPECIAL,
            $newMinStay
        );

        if ($merged['error'] !== null) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode($merged['error']));
        }

        try {
            $pdo->beginTransaction();
            $keepGlobalDiscounts = lh_property_stay_discounts_load_by_property($propertyId)['global'];
            lh_property_pricing_periods_save($pdo, $propertyId, $merged['periods'], $keepGlobalDiscounts);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('calendar special_price: '.$e->getMessage());

            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Salvarea a eșuat. Încearcă din nou.'));
        }

        $conn = LegacyBridge::createMysqliConnection();
        lh_admin_log_activity($conn, 'calendar_pricing_special', 'property', $propertyId, [
            'range_start' => $rangeStart,
            'range_end_exclusive' => $rangeEndEx,
            'price' => $price,
            'price_weekend' => $pw,
            'min_stay' => $newMinStay,
        ]);
        mysqli_close($conn);

        return redirect()->to($redirectTo.'&flash_ok=1');
    }

    private function handleBookingCancel(Request $request, PDO $pdo, string $redirectTo): RedirectResponse
    {
        if (! lh_csrf_verify_post()) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Sesiune invalidă. Reîncarcă pagina și încearcă din nou.'));
        }

        $bookingId = (int) $request->input('booking_id', 0);
        if ($bookingId < 1) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Rezervare invalidă.'));
        }

        $booking = null;
        try {
            $pdo->beginTransaction();
            $stmtB = $pdo->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
            $stmtB->execute([$bookingId]);
            $booking = $stmtB->fetch(PDO::FETCH_ASSOC);
            if (! $booking) {
                $pdo->rollBack();

                return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Rezervarea nu a fost găsită.'));
            }
            if (($booking['status'] ?? '') === 'cancelled') {
                $pdo->rollBack();

                return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Rezervarea este deja anulată.'));
            }
            $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
            $del = $pdo->prepare(
                'DELETE FROM blocked_dates WHERE property_id = ? AND source = ? AND external_event_id = ?'
            );
            $del->execute([(int) $booking['property_id'], 'direct_booking', 'booking-'.$bookingId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('calendar booking_cancel: '.$e->getMessage());

            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Anularea a eșuat. Încearcă din nou.'));
        }

        $conn = LegacyBridge::createMysqliConnection();
        lh_admin_log_activity($conn, 'booking_cancel', 'booking', $bookingId, [
            'property_id' => (int) ($booking['property_id'] ?? 0),
            'source' => 'calendar',
        ]);
        mysqli_close($conn);

        return redirect()->to($redirectTo.'&flash_ok=booking_cancelled');
    }

    private function handleBookingUpdate(Request $request, PDO $pdo, string $redirectTo): RedirectResponse
    {
        if (! lh_csrf_verify_post()) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode('Sesiune invalidă. Reîncarcă pagina și încearcă din nou.'));
        }

        $bookingId = (int) $request->input('booking_id', 0);
        $updateOut = lh_admin_process_booking_update($pdo, $request->all());
        if (empty($updateOut['ok'])) {
            return redirect()->to($redirectTo.'&flash_err='.rawurlencode((string) ($updateOut['message'] ?? 'Salvarea a eșuat.')));
        }

        $conn = LegacyBridge::createMysqliConnection();
        lh_admin_log_activity($conn, 'booking_update', 'booking', $bookingId, [
            'property_id' => (int) ($updateOut['property_id'] ?? 0),
            'check_in' => (string) ($updateOut['check_in'] ?? ''),
            'check_out' => (string) ($updateOut['check_out'] ?? ''),
            'source' => 'calendar',
        ]);
        mysqli_close($conn);

        return redirect()->to($redirectTo.'&flash_ok=booking_updated');
    }

    private static function toYmd(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = (string) $value;

        return strlen($string) >= 10 ? substr($string, 0, 10) : $string;
    }
}
