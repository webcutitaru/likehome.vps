<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IcalExportService;
use Illuminate\Http\Response;

final class IcalExportController extends Controller
{
    public function __construct(
        private readonly IcalExportService $service,
    ) {}

    public function show(string $token): Response
    {
        $result = $this->service->export($token);

        $response = response($result['body'], $result['status'])
            ->header('Content-Type', $result['content_type']);

        if (isset($result['content_disposition'])) {
            $response->header('Content-Disposition', $result['content_disposition']);
        }

        return $response;
    }
}
