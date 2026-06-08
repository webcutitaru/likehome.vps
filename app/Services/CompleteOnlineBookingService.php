<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class CompleteOnlineBookingService
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function complete(): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/complete_online_booking.php');

        return lh_api_complete_online_booking();
    }
}
