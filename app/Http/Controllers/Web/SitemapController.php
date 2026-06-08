<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Models\Property;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        LegacyBridge::boot();

        $now = gmdate('Y-m-d');
        $locales = config('likehome.locales', ['ro', 'en', 'ru']);

        $static = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['route' => 'properties.index', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'about', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($locales as $locale) {
            foreach ($static as $row) {
                $loc = route("{$locale}.{$row['route']}");
                $xml .= $this->urlNode($loc, $now, $row['changefreq'], $row['priority']);
            }
        }

        $properties = Property::query()->where('is_active', true)->orderBy('id')->get();
        foreach ($properties as $property) {
            foreach ($locales as $locale) {
                $localized = LegacyBridge::applyLocale($property->toLegacyArray());
                $slug = trim((string) ($localized['slug'] ?? $property->slug));
                if ($slug === '') {
                    continue;
                }
                $loc = route("{$locale}.properties.show", ['slug' => $slug]);
                $lastmod = $property->created_at?->format('Y-m-d') ?? $now;
                $xml .= $this->urlNode($loc, $lastmod, 'weekly', '0.8');
            }
        }

        $xml .= "</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function urlNode(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '  <url>'
            . '<loc>' . $esc($loc) . '</loc>'
            . '<lastmod>' . $esc($lastmod) . '</lastmod>'
            . '<changefreq>' . $esc($changefreq) . '</changefreq>'
            . '<priority>' . $esc($priority) . '</priority>'
            . "</url>\n";
    }
}
