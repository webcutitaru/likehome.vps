<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CanonicalHostRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = parse_url((string) config('likehome.site_url'), PHP_URL_HOST);
        if (! is_string($canonicalHost) || $canonicalHost === '') {
            return $next($request);
        }

        if ($request->getHost() !== 'www.'.$canonicalHost) {
            return $next($request);
        }

        $scheme = $request->isSecure() ? 'https' : 'http';

        return redirect()->away($scheme.'://'.$canonicalHost.$request->getRequestUri(), 301);
    }
}
