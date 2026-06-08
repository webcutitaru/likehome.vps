<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Legacy\LegacyBridge;
use DateTimeImmutable;
use Illuminate\Console\Command;
use PDO;

class SendCheckinRemindersCommand extends Command
{
    protected $signature = 'bookings:send-checkin-reminders';

    protected $description = 'Send reminder emails ~24h before guest check-in';

    public function handle(): int
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/checkin_reminder_send.php');
        require_once base_path('app/Legacy/includes/booking_locale.php');

        $pdo = LegacyBridge::pdo();
        $adminEmail = lh_booking_resolve_admin_notification_email();
        $telegramBotToken = defined('TELEGRAM_BOT_TOKEN') ? trim((string) TELEGRAM_BOT_TOKEN) : '';
        $telegramChatId = defined('TELEGRAM_CHAT_ID') ? trim((string) TELEGRAM_CHAT_ID) : '';

        $sql = <<<'SQL'
SELECT b.id AS booking_id, b.guest_name, b.guest_email, b.guest_phone, b.check_in, b.check_out, b.guests, b.total_price, b.locale,
       p.id AS property_id, p.title AS property_title, p.check_in_start, p.check_in_end, p.check_out_end,
       p.address, p.city, p.district, p.floor,
       p.pre_checkin_email_message
FROM bookings b
INNER JOIN properties p ON p.id = b.property_id
WHERE b.status = 'confirmed'
  AND b.checkin_reminder_sent_at IS NULL
  AND b.check_in >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
  AND b.check_in <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
ORDER BY b.check_in ASC, b.id ASC
SQL;

        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $now = new DateTimeImmutable('now');
        $processed = 0;
        $sent = 0;
        $skipped = 0;
        $errors = 0;

        $sendOpts = [
            'enforce_24h_window' => true,
            'admin_email' => $adminEmail,
            'telegram_bot_token' => $telegramBotToken,
            'telegram_chat_id' => $telegramChatId,
            'sent_at_update_mode' => 'if_null',
            'log_context' => 'checkin_reminder',
        ];

        foreach ($rows as $row) {
            ++$processed;
            $out = lh_checkin_reminder_send_for_booking_row($pdo, $row, $now, $sendOpts);
            if ($out['result'] === 'sent') {
                ++$sent;
            } elseif ($out['result'] === 'skipped') {
                ++$skipped;
            } else {
                ++$errors;
            }
        }

        $this->info('LikeHome check-in reminder');
        $this->line("candidates: {$processed}, sent: {$sent}, skipped (not due or past check-in): {$skipped}, errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
