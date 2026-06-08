<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Services\BookedDatesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookedDatesController extends Controller
{
    public function __construct(
        private readonly BookedDatesService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        LegacyBridge::syncRequest($request);

        $propertyId = filter_var($request->query('property_id'), FILTER_VALIDATE_INT);
        $result = $this->service->get($propertyId ? (int) $propertyId : 0);

        return response()
            ->json($result['body'], $result['status'])
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
