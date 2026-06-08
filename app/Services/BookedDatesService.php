<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class BookedDatesService
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function get(int $propertyId): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/booked_dates.php');

        return lh_api_get_booked_dates($propertyId);
    }
}
