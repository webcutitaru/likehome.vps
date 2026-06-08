<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\AdminCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CalendarActionController
{
    public function __invoke(Request $request, AdminCalendarService $service): RedirectResponse
    {
        return $service->handlePost($request);
    }
}
