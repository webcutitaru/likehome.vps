<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Legacy\LegacyBridge;
use Illuminate\Console\Command;
use PDO;

class ExpirePendingBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Expire unpaid online bookings past payment TTL';

    public function handle(): int
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/booking_confirm.php');

        $pdo = LegacyBridge::pdo();
        $stmt = $pdo->query("
            SELECT id, property_id
            FROM bookings
            WHERE status = 'pending'
              AND payment_method = 'online'
              AND payment_status = 'pending'
              AND payment_expires_at IS NOT NULL
              AND payment_expires_at < NOW()
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $count = 0;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $pdo->prepare(
                "UPDATE bookings SET status = 'cancelled', payment_status = 'failed', cancelled_at = NOW(), payment_expires_at = NULL WHERE id = ?"
            )->execute([$id]);
            lh_booking_release_blocked_dates($pdo, (int) ($row['property_id'] ?? 0), $id);
            ++$count;
        }

        $this->info('LikeHome expire pending bookings');
        $this->line("expired: {$count}");

        return self::SUCCESS;
    }
}
