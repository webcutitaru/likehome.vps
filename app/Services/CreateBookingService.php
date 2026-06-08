<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class CreateBookingService
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function create(): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/create_booking.php');

        return lh_api_create_booking();
    }
}
