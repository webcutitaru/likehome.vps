<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Legacy\LegacyBridge;
use Illuminate\Console\Command;
use PDO;

class SendPaymentReminderForBookingCommand extends Command
{
    protected $signature = 'bookings:send-payment-reminder-for {bookingId : Booking ID}';

    protected $description = 'Send payment reminder email for a single booking (scheduled after booking creation)';

    public function handle(): int
    {
        $bookingId = (int) $this->argument('bookingId');
        if ($bookingId < 1) {
            $this->error('Invalid booking ID');

            return self::FAILURE;
        }

        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/payment_reminder_send.php');
        require_once base_path('app/Legacy/includes/booking_payment.php');
        require_once base_path('app/Legacy/includes/booking_locale.php');

        $pdo = LegacyBridge::pdo();

        if (!lh_bookings_has_column($pdo, 'payment_reminder_sent_at')) {
            $this->warn('Column payment_reminder_sent_at missing — run php artisan migrate');

            return self::SUCCESS;
        }

        $stmt = $pdo->prepare(
            'SELECT b.*, p.title, p.slug
             FROM bookings b
             INNER JOIN properties p ON p.id = b.property_id
             WHERE b.id = ?
             LIMIT 1'
        );
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $this->warn("Booking #{$bookingId} not found");

            return self::SUCCESS;
        }

        $out = lh_payment_reminder_send_for_booking_row($pdo, $row, [
            'log_context' => 'payment_reminder_scheduled',
        ]);

        $this->line("booking #{$bookingId}: {$out['result']}" . (isset($out['reason']) ? " ({$out['reason']})" : ''));

        return $out['result'] === 'error' ? self::FAILURE : self::SUCCESS;
    }
}
