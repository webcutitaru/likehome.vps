<?php

namespace App\Http\Middleware;

use App\Legacy\LegacyBridge;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    private array $supported;

    private string $defaultLocale;

    public function __construct()
    {
        $this->supported = config('likehome.locales', ['ro', 'en', 'ru']);
        $this->defaultLocale = config('likehome.default_locale', 'ro');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);

        if ($locale !== $this->defaultLocale) {
            $_SERVER['REDIRECT_LH_LANG'] = $locale;
        } else {
            unset($_SERVER['REDIRECT_LH_LANG']);
        }

        LegacyBridge::boot();

        return $next($request);
    }

    private function detectLocale(Request $request): string
    {
        $segment = $request->segment(1);

        if (is_string($segment) && in_array($segment, $this->prefixLocales(), true)) {
            return $segment;
        }

        return $this->defaultLocale;
    }

    /** @return list<string> */
    private function prefixLocales(): array
    {
        return array_values(array_filter(
            $this->supported,
            fn (string $loc): bool => $loc !== $this->defaultLocale
        ));
    }
}
