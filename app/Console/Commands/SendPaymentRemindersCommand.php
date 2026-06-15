<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Legacy\LegacyBridge;
use Illuminate\Console\Command;
use PDO;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'bookings:send-payment-reminders';

    protected $description = 'Send guest payment reminder if online booking is still unpaid after the delay';

    public function handle(): int
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/payment_reminder_send.php');
        require_once base_path('app/Legacy/includes/booking_payment.php');
        require_once base_path('app/Legacy/includes/booking_locale.php');

        $pdo = LegacyBridge::pdo();

        if (!lh_bookings_has_column($pdo, 'payment_reminder_sent_at')) {
            $this->warn('Column payment_reminder_sent_at missing — run php artisan migrate');

            return self::SUCCESS;
        }

        $afterMinutes = lh_booking_payment_reminder_after_minutes();
        $sql = "
            SELECT b.*, p.title, p.slug
            FROM bookings b
            INNER JOIN properties p ON p.id = b.property_id
            WHERE b.status = 'pending'
              AND b.payment_method = 'online'
              AND b.payment_status = 'pending'
              AND b.payment_reminder_sent_at IS NULL
              AND b.payment_expires_at IS NOT NULL
              AND b.payment_expires_at > NOW()
              AND b.created_at <= DATE_SUB(NOW(), INTERVAL {$afterMinutes} MINUTE)
            ORDER BY b.id ASC
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $processed = 0;
        $sent = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            ++$processed;
            $out = lh_payment_reminder_send_for_booking_row($pdo, $row, [
                'log_context' => 'payment_reminder_cron',
            ]);
            if ($out['result'] === 'sent') {
                ++$sent;
            } elseif ($out['result'] === 'skipped') {
                ++$skipped;
            } else {
                ++$errors;
            }
        }

        $this->info('LikeHome payment reminder');
        $this->line("after_minutes: {$afterMinutes}, candidates: {$processed}, sent: {$sent}, skipped: {$skipped}, errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
