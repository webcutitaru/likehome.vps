<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class IcalExportService
{
    /**
     * @return array{status: int, body: string, content_type: string, content_disposition?: string}
     */
    public function export(string $token): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/ical_export.php');

        return lh_api_ical_export($token);
    }
}
