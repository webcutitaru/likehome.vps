<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;

final class PropertyFilterService
{
    /**
     * @param array<string, mixed> $input
     * @return array{status: int, body: string, content_type: string}
     */
    public function filter(array $input): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/Api/property_filter.php');

        return lh_api_filter_properties($input);
    }
}
