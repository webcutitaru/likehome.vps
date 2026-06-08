<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy bootstrap may send a strict CSP via PHP header() during render.
 * Filament/Livewire need eval; strip CSP from admin panel responses.
 */
class StripLegacyCspForAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! headers_sent()) {
            header_remove('Content-Security-Policy');
        }

        $response->headers->remove('Content-Security-Policy');

        return $response;
    }
}
