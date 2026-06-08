<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Services\CompleteOnlineBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompleteOnlineBookingController extends Controller
{
    public function __construct(
        private readonly CompleteOnlineBookingService $service,
    ) {}

    public function store(Request $request): JsonResponse
    {
        LegacyBridge::syncRequest($request);

        $result = $this->service->complete();

        return response()
            ->json($result['body'], $result['status'], [], JSON_UNESCAPED_UNICODE)
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
