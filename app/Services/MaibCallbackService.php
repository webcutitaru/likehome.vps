<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class MaibCallbackService
{
    /**
     * @param array<string, string|null> $headers
     * @return array{status: int, body: string, content_type?: string}
     */
    public function handle(string $rawBody, array $headers): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/maib_callback.php');

        return lh_api_maib_callback($rawBody, $headers);
    }
}
