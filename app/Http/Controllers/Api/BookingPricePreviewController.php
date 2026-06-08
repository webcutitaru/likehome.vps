<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Services\BookingPricePreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookingPricePreviewController extends Controller
{
    public function __construct(
        private readonly BookingPricePreviewService $service,
    ) {}

    public function store(Request $request): JsonResponse
    {
        LegacyBridge::syncRequest($request);

        $result = $this->service->preview();

        return response()
            ->json($result['body'], $result['status'], [], JSON_UNESCAPED_UNICODE)
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
