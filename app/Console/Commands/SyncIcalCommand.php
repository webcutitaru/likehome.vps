<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Legacy\LegacyBridge;
use Illuminate\Console\Command;
use PDO;

class SyncIcalCommand extends Command
{
    protected $signature = 'ical:sync';

    protected $description = 'Import iCal feeds for all properties with ical_import_link set';

    public function handle(): int
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/ical_importer.php');

        $pdo = LegacyBridge::pdo();
        $stmt = $pdo->query(
            "SELECT id FROM properties WHERE TRIM(COALESCE(ical_import_link, '')) <> '' ORDER BY id ASC"
        );
        $ids = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        if ($ids === false) {
            $ids = [];
        }

        $ok = 0;
        $fail = 0;
        $lines = [];

        foreach ($ids as $pid) {
            $propertyId = (int) $pid;
            $result = importPropertyIcal($propertyId);
            if (! empty($result['success'])) {
                ++$ok;
                $lines[] = sprintf(
                    'property %d: ok, imported %d',
                    $propertyId,
                    (int) ($result['imported'] ?? 0)
                );
            } else {
                ++$fail;
                $lines[] = sprintf(
                    'property %d: fail — %s',
                    $propertyId,
                    (string) ($result['error'] ?? 'unknown')
                );
            }
        }

        $total = count($ids);
        $this->info('LikeHome iCal sync');
        $this->line("properties: {$total}, ok: {$ok}, failed: {$fail}");
        foreach ($lines as $line) {
            $this->line($line);
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
