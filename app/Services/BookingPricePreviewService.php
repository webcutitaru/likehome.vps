<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class BookingPricePreviewService
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function preview(): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/booking_price_preview.php');

        return lh_api_booking_price_preview();
    }
}
