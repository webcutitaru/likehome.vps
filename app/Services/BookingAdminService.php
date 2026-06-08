<?php

namespace App\Services;

use App\Legacy\LegacyBridge;
use App\Models\Booking;
use DateTimeImmutable;
use PDO;
use Throwable;

final class BookingAdminService
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function confirm(Booking $booking, ?int $actorUserId = null): array
    {
        $this->bootBookingIncludes();

        $pdo = LegacyBridge::pdo();
        $bookingId = (int) $booking->id;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookingId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $row) {
                $pdo->rollBack();

                return ['ok' => false, 'message' => 'Rezervarea nu a fost găsită.'];
            }

            if (($row['status'] ?? '') === 'cancelled') {
                $pdo->rollBack();

                return ['ok' => false, 'message' => 'Nu poți confirma o rezervare anulată.'];
            }

            if (($row['status'] ?? '') === 'confirmed') {
                $pdo->commit();

                return ['ok' => true, 'message' => 'Rezervarea este deja confirmată.'];
            }

            $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);

            $propertyId = (int) $row['property_id'];
            $checkIn = (string) $row['check_in'];
            $checkOut = (string) $row['check_out'];
            $externalEventId = 'booking-'.$bookingId;

            $exists = $pdo->prepare(
                'SELECT id FROM blocked_dates WHERE property_id = ? AND source = ? AND external_event_id = ? LIMIT 1'
            );
            $exists->execute([$propertyId, 'direct_booking', $externalEventId]);

            if (! $exists->fetch()) {
                $ins = $pdo->prepare(
                    'INSERT INTO blocked_dates (property_id, start_date, end_date, source, external_event_id, notes)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $propertyId,
                    $checkIn,
                    $checkOut,
                    'direct_booking',
                    $externalEventId,
                    'Booking #'.$bookingId,
                ]);
            }

            $pdo->commit();

            $this->activityLog->log('booking_confirm', 'booking', $bookingId, [
                'property_id' => $propertyId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ], $actorUserId);

            return ['ok' => true, 'message' => 'Rezervarea a fost confirmată.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'message' => 'Confirmarea a eșuat: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function cancel(Booking $booking, ?int $actorUserId = null): array
    {
        $this->bootBookingIncludes();

        $pdo = LegacyBridge::pdo();
        $bookingId = (int) $booking->id;

        $result = lh_booking_cancel_booking($pdo, $bookingId);

        if (! empty($result['ok'])) {
            $this->activityLog->log('booking_cancel', 'booking', $bookingId, [
                'property_id' => (int) $booking->property_id,
            ], $actorUserId);
        }

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? 'Anularea a eșuat.'),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function sendCheckinReminder(Booking $booking, ?int $actorUserId = null): array
    {
        $this->bootBookingIncludes();

        $pdo = LegacyBridge::pdo();
        $bookingId = (int) $booking->id;

        $sql = <<<'SQL'
SELECT b.id AS booking_id, b.guest_name, b.guest_email, b.guest_phone, b.check_in, b.check_out, b.guests, b.total_price,
       p.id AS property_id, p.title AS property_title, p.check_in_start, p.check_in_end, p.check_out_end,
       p.address, p.city, p.district, p.floor,
       p.pre_checkin_email_message
FROM bookings b
INNER JOIN properties p ON p.id = b.property_id
WHERE b.id = ? AND b.status = 'confirmed' AND b.check_out >= CURDATE()
LIMIT 1
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $row) {
            return [
                'ok' => false,
                'message' => 'Rezervarea nu există, nu este confirmată sau sejurul este deja încheiat.',
            ];
        }

        $now = new DateTimeImmutable('now');
        $adminEmail = function_exists('lh_booking_resolve_admin_notification_email')
            ? lh_booking_resolve_admin_notification_email()
            : '';
        $tgToken = defined('TELEGRAM_BOT_TOKEN') ? trim((string) TELEGRAM_BOT_TOKEN) : '';
        $tgChat = defined('TELEGRAM_CHAT_ID') ? trim((string) TELEGRAM_CHAT_ID) : '';

        $out = lh_checkin_reminder_send_for_booking_row($pdo, $row, $now, [
            'enforce_24h_window' => false,
            'admin_email' => $adminEmail,
            'telegram_bot_token' => $tgToken,
            'telegram_chat_id' => $tgChat,
            'sent_at_update_mode' => 'always',
            'log_context' => 'checkin_reminder_admin',
        ]);

        if (($out['result'] ?? '') === 'sent') {
            $this->activityLog->log('booking_checkin_reminder_manual', 'booking', $bookingId, [
                'property_id' => (int) ($row['property_id'] ?? 0),
            ], $actorUserId);

            return ['ok' => true, 'message' => 'Reminder-ul check-in a fost trimis la oaspete.'];
        }

        $reason = (string) ($out['reason'] ?? '');

        return match ($reason) {
            'invalid_guest_email' => ['ok' => false, 'message' => 'Emailul oaspetelui nu este valid.'],
            'client_mail_failed' => ['ok' => false, 'message' => 'Trimiterea emailului a eșuat. Verifică configurația de mail a serverului.'],
            default => ['ok' => false, 'message' => ($out['result'] ?? '') === 'skipped'
                ? 'Reminder-ul nu a putut fi trimis (date check-in invalide).'
                : 'Nu s-a putut trimite reminder-ul.'],
        };
    }

    /**
     * @return array{ok: bool, message: string, refund_id?: string, refunded_amount?: float, remaining?: float}
     */
    public function refund(Booking $booking, ?float $amount, ?string $reason, ?int $actorUserId = null): array
    {
        $this->bootBookingIncludes();

        $pdo = LegacyBridge::pdo();
        $bookingId = (int) $booking->id;

        $refundOut = lh_booking_process_maib_refund($pdo, $bookingId, $amount, $reason);

        if (! empty($refundOut['ok'])) {
            $this->activityLog->log('booking_refund', 'booking', $bookingId, [
                'refund_id' => (string) ($refundOut['refund_id'] ?? ''),
                'refunded_amount' => (float) ($refundOut['refunded_amount'] ?? 0),
                'remaining' => (float) ($refundOut['remaining'] ?? 0),
            ], $actorUserId);
        }

        return [
            'ok' => (bool) ($refundOut['ok'] ?? false),
            'message' => (string) ($refundOut['message'] ?? 'Rambursarea a eșuat.'),
            'refund_id' => (string) ($refundOut['refund_id'] ?? ''),
            'refunded_amount' => (float) ($refundOut['refunded_amount'] ?? 0),
            'remaining' => (float) ($refundOut['remaining'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message: string, property_id?: int, check_in?: string, check_out?: string}
     */
    public function updateBooking(Booking $booking, array $payload, ?int $actorUserId = null): array
    {
        $this->bootBookingIncludes();
        require_once base_path('app/Legacy/includes/booking_admin.php');

        $pdo = LegacyBridge::pdo();
        $payload['booking_id'] = (int) $booking->id;

        $updateOut = lh_admin_process_booking_update($pdo, $payload);

        if (! empty($updateOut['ok'])) {
            $this->activityLog->log('booking_update', 'booking', (int) $booking->id, [
                'property_id' => (int) ($updateOut['property_id'] ?? 0),
                'check_in' => (string) ($updateOut['check_in'] ?? ''),
                'check_out' => (string) ($updateOut['check_out'] ?? ''),
                'source' => 'bookings',
            ], $actorUserId);
        }

        return [
            'ok' => (bool) ($updateOut['ok'] ?? false),
            'message' => (string) ($updateOut['message'] ?? 'Salvarea a eșuat.'),
            'property_id' => (int) ($updateOut['property_id'] ?? 0),
            'check_in' => (string) ($updateOut['check_in'] ?? ''),
            'check_out' => (string) ($updateOut['check_out'] ?? ''),
        ];
    }

    private function bootBookingIncludes(): void
    {
        LegacyBridge::boot();

        $base = base_path('app/Legacy/includes');

        require_once $base.'/booking_payment.php';
        require_once $base.'/booking_confirm.php';
        require_once $base.'/booking_refund.php';
        require_once $base.'/booking_admin.php';
        require_once $base.'/checkin_reminder_send.php';
    }
}
