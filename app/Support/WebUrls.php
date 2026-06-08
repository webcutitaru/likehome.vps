<?php

namespace App\Support;

use App\Legacy\LegacyBridge;

class WebUrls
{
    /** @return array<string, string> */
    public static function localeFlags(): array
    {
        return ['ro' => '🇲🇩', 'en' => '🇬🇧', 'ru' => '🇷🇺'];
    }

    public static function home(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return route("{$locale}.home");
    }

    public static function propertiesIndex(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return route("{$locale}.properties.index");
    }

    /**
     * @param  array<string, mixed>  $property
     */
    public static function propertyShow(array $property, ?string $locale = null, array $query = []): string
    {
        $locale = $locale ?? app()->getLocale();
        LegacyBridge::boot();
        $slug = lh_property_locale_slug($property, $locale);
        if ($slug === '') {
            $slug = (string) ($property['slug'] ?? $property['id'] ?? '');
        }

        $url = route("{$locale}.properties.show", ['slug' => $slug]);
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    public static function page(string $page, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return route("{$locale}.{$page}");
    }

    public static function searchAnchor(?string $locale = null): string
    {
        return self::home($locale) . '#search-bar';
    }

    /**
     * @return list<array{label: string, href: string, route: string, current: bool}>
     */
    public static function navItems(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return [
            ['label' => __('nav.home'), 'href' => self::home($locale), 'route' => "{$locale}.home"],
            ['label' => __('nav.properties'), 'href' => self::propertiesIndex($locale), 'route' => "{$locale}.properties.index"],
            ['label' => __('nav.faq'), 'href' => self::page('faq', $locale), 'route' => "{$locale}.faq"],
            ['label' => __('nav.about'), 'href' => self::page('about', $locale), 'route' => "{$locale}.about"],
            ['label' => __('nav.contact'), 'href' => self::page('contact', $locale), 'route' => "{$locale}.contact"],
        ];
    }

    public static function isCurrentNav(string $routeName): bool
    {
        $current = request()->route()?->getName() ?? '';

        if (str_ends_with($routeName, '.properties.index') && str_contains($current, '.properties.')) {
            return true;
        }

        return $current === $routeName;
    }

    /**
     * @return list<array{locale: string, label: string, name: string, flag: string, href: string, current: bool}>
     */
    public static function localeSwitcherItems(): array
    {
        $currentRoute = request()->route()?->getName();
        $params = request()->route()?->parameters() ?? [];
        $page = 'home';
        if (is_string($currentRoute) && str_contains($currentRoute, '.')) {
            $page = substr($currentRoute, strpos($currentRoute, '.') + 1);
        }
        $items = [];

        foreach (config('likehome.locales', ['ro', 'en', 'ru']) as $locale) {
            $href = $currentRoute
                ? route("{$locale}.{$page}", $params)
                : self::home($locale);

            $items[] = [
                'locale' => $locale,
                'label' => strtoupper($locale === 'ro' ? 'RO' : $locale),
                'name' => __('lang.' . $locale),
                'flag' => self::localeFlags()[$locale] ?? '',
                'href' => $href,
                'current' => $locale === app()->getLocale(),
            ];
        }

        return $items;
    }
}
