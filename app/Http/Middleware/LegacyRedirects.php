<?php

namespace App\Http\Middleware;

use App\Legacy\LegacyBridge;
use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirects
{
    /** @var array<string, array<string, string>> */
    private const PAGE_MAP = [
        'ro' => [
            'index.php' => 'ro.home',
            'properties.php' => 'ro.properties.index',
            'about.php' => 'ro.about',
            'contact.php' => 'ro.contact',
            'faq.php' => 'ro.faq',
            'privacy.php' => 'ro.privacy',
            'terms.php' => 'ro.terms',
            'booking-payment-success.php' => 'ro.booking.success',
            'booking-payment-failed.php' => 'ro.booking.failed',
            'sitemap.php' => 'sitemap',
        ],
        'en' => [
            'index.php' => 'en.home',
            'properties.php' => 'en.properties.index',
            'about.php' => 'en.about',
            'contact.php' => 'en.contact',
            'faq.php' => 'en.faq',
            'privacy.php' => 'en.privacy',
            'terms.php' => 'en.terms',
            'booking-payment-success.php' => 'en.booking.success',
            'booking-payment-failed.php' => 'en.booking.failed',
        ],
        'ru' => [
            'index.php' => 'ru.home',
            'properties.php' => 'ru.properties.index',
            'about.php' => 'ru.about',
            'contact.php' => 'ru.contact',
            'faq.php' => 'ru.faq',
            'privacy.php' => 'ru.privacy',
            'terms.php' => 'ru.terms',
            'booking-payment-success.php' => 'ru.booking.success',
            'booking-payment-failed.php' => 'ru.booking.failed',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $target = $this->resolveRedirect($request);
        if ($target !== null) {
            return redirect()->to($target, 301);
        }

        return $next($request);
    }

    private function resolveRedirect(Request $request): ?string
    {
        $path = $request->path();
        if (! str_ends_with($path, '.php') && $path !== 'sitemap.php') {
            return null;
        }

        $locale = 'ro';
        $script = basename($path);

        if (preg_match('#^(en|ru)/(.+\.php)$#', $path, $m)) {
            $locale = $m[1];
            $script = $m[2];
        }

        if ($script === 'property-details.php') {
            return $this->redirectPropertyDetails($request, $locale);
        }

        $routeName = self::PAGE_MAP[$locale][$script] ?? null;
        if ($routeName === null) {
            return null;
        }

        if ($routeName === 'sitemap') {
            return url('/sitemap.xml') . $this->querySuffix($request);
        }

        return route($routeName) . $this->querySuffix($request);
    }

    private function redirectPropertyDetails(Request $request, string $locale): ?string
    {
        $slug = trim((string) $request->query('slug', ''));
        if ($slug !== '') {
            return route("{$locale}.properties.show", ['slug' => $slug]) . $this->filteredQuery($request, ['slug', 'id']);
        }

        $id = (int) $request->query('id', 0);
        if ($id > 0) {
            $property = Property::query()->where('id', $id)->where('is_active', true)->first();
            if ($property) {
                LegacyBridge::boot();
                $localized = LegacyBridge::applyLocale($property->toLegacyArray());
                $resolvedSlug = trim((string) ($localized['slug'] ?? $property->slug));

                if ($resolvedSlug !== '') {
                    return route("{$locale}.properties.show", ['slug' => $resolvedSlug]) . $this->filteredQuery($request, ['slug', 'id']);
                }
            }
        }

        return route("{$locale}.properties.index");
    }

    private function querySuffix(Request $request): string
    {
        $query = $request->query();
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    /** @param  list<string>  $exclude */
    private function filteredQuery(Request $request, array $exclude): string
    {
        $query = collect($request->query())->except($exclude)->all();
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }
}
