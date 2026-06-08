<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filament\Pages\PropertyCalendar;
use App\Filament\Resources\BookingResource;
use App\Legacy\LegacyBridge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

final class BookingActionController
{
    public function __invoke(Request $request): RedirectResponse
    {
        LegacyBridge::boot();
        LegacyBridge::syncRequest($request);

        $pdo = LegacyBridge::pdo();
        $action = (string) $request->input('action', '');
        $bookingId = (int) $request->input('booking_id', 0);
        $returnPage = (string) $request->input('return_page', 'bookings');

        $redirectTo = $returnPage === 'calendar'
            ? PropertyCalendar::getUrl().'?from='.rawurlencode((string) $request->input('redirect_from', date('Y-m-d')))
                .'&days='.max(1, min(120, (int) $request->input('redirect_days', 60)))
            : BookingResource::getUrl('index');

        if ($bookingId < 1) {
            return redirect()->to($redirectTo.'?flash_err='.rawurlencode('Rezervare invalidă.'));
        }

        if (! lh_csrf_verify_post()) {
            return redirect()->to($redirectTo.'?flash_err='.rawurlencode('Sesiune invalidă. Reîncarcă pagina și încearcă din nou.'));
        }

        if ($action === 'refund') {
            require_once base_path('app/Legacy/includes/booking_refund.php');
            $refundAmountRaw = trim((string) $request->input('refund_amount', ''));
            $refundAmount = $refundAmountRaw === '' ? null : (float) str_replace(',', '.', $refundAmountRaw);
            $refundReason = trim((string) $request->input('refund_reason', ''));
            $refundOut = lh_booking_process_maib_refund(
                $pdo,
                $bookingId,
                $refundAmount,
                $refundReason !== '' ? $refundReason : null
            );
            if (! empty($refundOut['ok'])) {
                $conn = LegacyBridge::createMysqliConnection();
                lh_admin_log_activity($conn, 'booking_refund', 'booking', $bookingId, [
                    'refund_id' => (string) ($refundOut['refund_id'] ?? ''),
                    'refunded_amount' => (float) ($refundOut['refunded_amount'] ?? 0),
                    'remaining' => (float) ($refundOut['remaining'] ?? 0),
                ]);
                mysqli_close($conn);

                return redirect()->to($redirectTo.'?flash_ok='.rawurlencode((string) ($refundOut['message'] ?? 'Rambursarea a fost inițiată.')));
            }

            return redirect()->to($redirectTo.'?flash_err='.rawurlencode((string) ($refundOut['message'] ?? 'Rambursarea a eșuat.')));
        }

        if ($action === 'update') {
            $updateOut = lh_admin_process_booking_update($pdo, $request->all());
            if (! empty($updateOut['ok'])) {
                $conn = LegacyBridge::createMysqliConnection();
                lh_admin_log_activity($conn, 'booking_update', 'booking', $bookingId, [
                    'property_id' => (int) ($updateOut['property_id'] ?? 0),
                    'check_in' => (string) ($updateOut['check_in'] ?? ''),
                    'check_out' => (string) ($updateOut['check_out'] ?? ''),
                    'source' => $returnPage,
                ]);
                mysqli_close($conn);

                return redirect()->to($redirectTo.'?flash_ok=booking_updated');
            }

            return redirect()->to($redirectTo.'?flash_err='.rawurlencode((string) ($updateOut['message'] ?? 'Salvarea a eșuat.')));
        }

        if ($action === 'cancel') {
            try {
                $pdo->beginTransaction();
                $stmtB = $pdo->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
                $stmtB->execute([$bookingId]);
                $booking = $stmtB->fetch(\PDO::FETCH_ASSOC);
                if (! $booking) {
                    $pdo->rollBack();

                    return redirect()->to($redirectTo.'?flash_err='.rawurlencode('Rezervarea nu a fost găsită.'));
                }
                if (($booking['status'] ?? '') === 'cancelled') {
                    $pdo->rollBack();

                    return redirect()->to($redirectTo.'?flash_err='.rawurlencode('Rezervarea este deja anulată.'));
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
                error_log('booking cancel: '.$e->getMessage());

                return redirect()->to($redirectTo.'?flash_err='.rawurlencode('Anularea a eșuat. Încearcă din nou.'));
            }

            $conn = LegacyBridge::createMysqliConnection();
            lh_admin_log_activity($conn, 'booking_cancel', 'booking', $bookingId, [
                'property_id' => (int) ($booking['property_id'] ?? 0),
                'source' => $returnPage,
            ]);
            mysqli_close($conn);

            return redirect()->to($redirectTo.'?flash_ok=booking_cancelled');
        }

        return redirect()->to($redirectTo);
    }
}
