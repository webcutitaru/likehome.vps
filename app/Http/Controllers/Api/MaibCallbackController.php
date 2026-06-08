<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Services\MaibCallbackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class MaibCallbackController extends Controller
{
    public function __construct(
        private readonly MaibCallbackService $service,
    ) {}

    public function store(Request $request): Response|SymfonyResponse
    {
        LegacyBridge::boot();
        $_SERVER['REQUEST_METHOD'] = $request->method();

        $rawBody = $request->getContent();
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = is_array($values) ? ($values[0] ?? null) : $values;
        }

        $result = $this->service->handle($rawBody, $headers);

        $response = response($result['body'], $result['status']);
        if (isset($result['content_type'])) {
            $response->header('Content-Type', $result['content_type']);
        }
        $response->header('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
