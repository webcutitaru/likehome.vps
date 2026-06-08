<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Support\WebUrls;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function about(): View
    {
        return $this->sectionPage('about', 'pages.static.about');
    }

    public function contact(): View
    {
        return $this->sectionPage('contact', 'pages.static.contact');
    }

    public function faq(): View
    {
        LegacyBridge::boot();
        $meta = lh_page_meta('faq');
        $items = lh_page_faq_items();

        return view('pages.static.faq', [
            'pageTitle' => $meta['title'],
            'pageDescription' => $meta['description'],
            'canonicalUrl' => WebUrls::page('faq'),
            'meta' => $meta,
            'faqItems' => $items,
            'lhJsonLd' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'inLanguage' => match (app()->getLocale()) {
                    'en' => 'en-US',
                    'ru' => 'ru-RU',
                    default => 'ro-MD',
                },
                'mainEntity' => collect($items)->map(fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                ])->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    public function privacy(): View
    {
        LegacyBridge::boot();

        return view('pages.static.privacy', [
            'pageTitle' => __('page.privacy.title'),
            'pageDescription' => __('page.privacy.description'),
            'canonicalUrl' => WebUrls::page('privacy'),
            'sections' => lh_page_sections('privacy'),
        ]);
    }

    public function terms(): View
    {
        LegacyBridge::boot();

        return view('pages.static.terms', [
            'pageTitle' => __('page.terms.title'),
            'pageDescription' => __('page.terms.description'),
            'canonicalUrl' => WebUrls::page('terms'),
            'sections' => lh_page_sections('terms'),
        ]);
    }

    private function sectionPage(string $page, string $view): View
    {
        LegacyBridge::boot();
        $meta = lh_page_meta($page);
        $sections = lh_page_sections($page);

        return view($view, [
            'pageTitle' => $meta['title'],
            'pageDescription' => $meta['description'],
            'canonicalUrl' => WebUrls::page($page),
            'meta' => $meta,
            'sections' => $sections,
        ]);
    }
}
