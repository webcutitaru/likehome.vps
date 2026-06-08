<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Services\PropertyFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PropertyFilterController extends Controller
{
    public function __construct(
        private readonly PropertyFilterService $service,
    ) {}

    public function store(Request $request): Response
    {
        LegacyBridge::syncRequest($request);

        $result = $this->service->filter($request->all());

        return response($result['body'], $result['status'])
            ->header('Content-Type', $result['content_type'])
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
