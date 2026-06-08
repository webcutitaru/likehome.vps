<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Models\Property;
use App\Support\WebUrls;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        LegacyBridge::boot();

        $propertyOptions = Property::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        $featured = Property::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(9)
            ->get()
            ->map(fn (Property $p) => $p->toLegacyArray())
            ->all();

        $featured = LegacyBridge::applyLocaleList($featured);

        $ogImage = '';
        if ($featured !== []) {
            $fp = $featured[0];
            $images = ! empty($fp['image_name']) ? array_filter(explode(',', (string) $fp['image_name'])) : [];
            if ($images !== []) {
                $bn = trim((string) $images[0]);
                if ($bn !== '') {
                    $ogImage = url(lh_property_image_url((int) ($fp['id'] ?? 0), $bn, 'full'));
                }
            }
        }

        return view('pages.home', [
            'pageTitle' => __('page.index.title'),
            'pageDescription' => __('page.index.description'),
            'canonicalUrl' => WebUrls::home(),
            'ogImage' => $ogImage,
            'properties' => $propertyOptions,
            'featuredProps' => $featured,
            'lhJsonLd' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Like HOME',
                'url' => config('likehome.site_url'),
                'description' => __('page.index.description'),
                'inLanguage' => match (app()->getLocale()) {
                    'en' => 'en-US',
                    'ru' => 'ru-RU',
                    default => 'ro-MD',
                },
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Like HOME',
                    'url' => config('likehome.site_url'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => lh_absolute_href(lh_public_url('assets/favicon.svg')),
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }
}
