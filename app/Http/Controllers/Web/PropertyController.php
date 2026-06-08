<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Models\Property;
use App\Support\WebUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        LegacyBridge::boot();

        $district = trim((string) $request->query('district', ''));
        $city = trim((string) $request->query('city', ''));

        $sectorOptions = Property::query()
            ->where('is_active', true)
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district')
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->values()
            ->all();

        $query = Property::query()->where('is_active', true)->orderByDesc('created_at');
        if ($district !== '') {
            $query->where('district', $district);
        } elseif ($city !== '') {
            $query->where('city', $city);
        }

        $properties = LegacyBridge::applyLocaleList(
            $query->get()->map(fn (Property $p) => $p->toLegacyArray())->all()
        );

        $pageTitle = __('page.properties.title_default');
        $pageDescription = __('page.properties.desc_default');
        $heroTitle = __('page.properties.hero_default');
        $heroSubtitle = __('page.properties.filter_hint');
        $propertyCount = count($properties);

        if ($district !== '') {
            $label = \lh_location_label($district);
            $pageTitle = __('page.properties.title_area', ['area' => $label]);
            $pageDescription = __('page.properties.desc_area', ['area' => $label, 'count' => (string) $propertyCount]);
            $heroTitle = __('page.properties.hero_in_area', ['area' => $label]);
            $heroSubtitle = __('page.properties.hero_browse', ['count' => (string) $propertyCount]);
        } elseif ($city !== '') {
            $label = \lh_location_label($city);
            $pageTitle = __('page.properties.title_city', ['city' => $label]);
            $pageDescription = __('page.properties.desc_city', ['city' => $label, 'count' => (string) $propertyCount]);
            $heroTitle = __('page.properties.hero_in_city', ['city' => $label]);
            $heroSubtitle = __('page.properties.hero_browse', ['count' => (string) $propertyCount]);
        }

        $canonicalUrl = WebUrls::propertiesIndex();
        if ($district !== '') {
            $canonicalUrl .= '?'.http_build_query(['district' => $district]);
        } elseif ($city !== '') {
            $canonicalUrl .= '?'.http_build_query(['city' => $city]);
        }

        return view('pages.properties.index', [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'canonicalUrl' => $canonicalUrl,
            'heroTitle' => $heroTitle,
            'heroSubtitle' => $heroSubtitle,
            'propertyCount' => $propertyCount,
            'properties' => $properties,
            'lhAreaDistrict' => $district,
            'lhAreaCity' => $city,
            'lhSectorOptions' => $sectorOptions,
            'lhSearchBarShowSector' => true,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        LegacyBridge::boot();

        $property = LegacyBridge::resolvePropertyBySlug($slug);
        if ($property === null) {
            $propertyModel = Property::query()
                ->where('is_active', true)
                ->where('slug', $slug)
                ->first();

            if ($propertyModel) {
                $property = LegacyBridge::applyLocale($propertyModel->toLegacyArray());
            }
        }

        abort_if($property === null, 404, __('errors.property_not_found'));

        $property['_pricing_periods'] = \lh_property_pricing_periods_load((int) $property['id']);
        $property['_stay_discounts_global'] = \lh_property_stay_discounts_load_by_property((int) $property['id'])['global'];

        $check_in = (string) $request->query('check_in', '');
        $check_out = (string) $request->query('check_out', '');
        $guests = (string) $request->query('guests', '');
        $has_checkin = $check_in !== '' && \DateTime::createFromFormat('Y-m-d', $check_in)?->format('Y-m-d') === $check_in;
        $has_checkout = $check_out !== '' && \DateTime::createFromFormat('Y-m-d', $check_out)?->format('Y-m-d') === $check_out;

        $pdo = LegacyBridge::pdo();
        $currentPropId = (int) $property['id'];
        $districtTrim = trim((string) ($property['district'] ?? ''));
        $cityTrim = trim((string) ($property['city'] ?? ''));
        $sameAreaProperties = [];
        $sameAreaSeeMoreUrl = '';
        $sameAreaLabel = '';

        if ($districtTrim !== '') {
            $rows = DB::table('properties')
                ->where('is_active', true)
                ->where('id', '!=', $currentPropId)
                ->where('district', $districtTrim)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();
            $sameAreaProperties = \lh_property_apply_locale_list(
                $rows->map(fn ($r) => (array) $r)->all(),
                $pdo
            );
            if ($sameAreaProperties !== []) {
                $sameAreaSeeMoreUrl = WebUrls::propertiesIndex().'?'.http_build_query(['district' => $districtTrim]);
                $sameAreaLabel = \lh_location_label($districtTrim);
            }
        } elseif ($cityTrim !== '') {
            $rows = DB::table('properties')
                ->where('is_active', true)
                ->where('id', '!=', $currentPropId)
                ->where('city', $cityTrim)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();
            $sameAreaProperties = \lh_property_apply_locale_list(
                $rows->map(fn ($r) => (array) $r)->all(),
                $pdo
            );
            if ($sameAreaProperties !== []) {
                $sameAreaSeeMoreUrl = WebUrls::propertiesIndex().'?'.http_build_query(['city' => $cityTrim]);
                $sameAreaLabel = \lh_location_label($cityTrim);
            }
        }

        $images = ! empty($property['image_name']) ? array_filter(explode(',', (string) $property['image_name'])) : [];
        $propertyIdForImages = (int) ($property['id'] ?? 0);

        $lhPropTitleRaw = trim((string) ($property['title'] ?? __('card.fallback_title')));
        if ($lhPropTitleRaw === '') {
            $lhPropTitleRaw = __('card.fallback_title');
        }

        $descSource = trim((string) ($property['description_long'] ?? ''));
        if ($descSource === '') {
            $descSource = trim((string) ($property['description'] ?? ''));
        }
        if ($descSource === '') {
            $locHint = trim(implode(', ', array_filter([
                trim((string) ($property['district'] ?? '')),
                trim((string) ($property['city'] ?? '')),
            ])));
            $descSource = $lhPropTitleRaw
                .($locHint !== '' ? ' — '.$locHint : '')
                .'. Cazare de închiriat în Moldova; verifică disponibilitatea și rezervă direct prin Like HOME.';
        }

        $pageDescription = \lh_seo_meta_plain($descSource);
        $canonicalUrl = WebUrls::propertyShow($property);
        $lhLocaleAlternateUrls = \lh_property_locale_alternate_urls($property, $pdo);

        $ogImage = '';
        if ($images !== []) {
            $firstImg = trim((string) $images[0]);
            if ($firstImg !== '') {
                $ogImage = \lh_absolute_href(\lh_property_image_url($propertyIdForImages, $firstImg, 'full'));
            }
        }

        $ldAddress = ['@type' => 'PostalAddress', 'addressCountry' => 'MD'];
        $cityLd = trim((string) ($property['city'] ?? ''));
        if ($cityLd !== '') {
            $ldAddress['addressLocality'] = $cityLd;
        }
        $streetLd = trim((string) ($property['address'] ?? ''));
        if ($streetLd !== '') {
            $ldAddress['streetAddress'] = $streetLd;
        }

        $ldGraph = [
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $lhPropTitleRaw,
            'description' => $pageDescription,
            'url' => $canonicalUrl,
            'inLanguage' => match (app()->getLocale()) {
                'en' => 'en-US',
                'ru' => 'ru-RU',
                default => 'ro-MD',
            },
        ];
        if ($ogImage !== '') {
            $ldGraph['image'] = [$ogImage];
        }
        $ldGraph['address'] = $ldAddress;
        $lhJsonLd = json_encode($ldGraph, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $amenities = json_decode($property['amenities'] ?? '[]', true);
        if (! is_array($amenities)) {
            $amenities = [];
        }

        $mapQuery = trim(implode(', ', array_filter([
            trim((string) ($property['address'] ?? '')),
            trim((string) ($property['district'] ?? '')),
            trim((string) ($property['city'] ?? '')),
        ])));

        $mapsEmbedKey = trim(\lh_env('GOOGLE_MAPS_EMBED_API_KEY', ''));
        if ($mapQuery !== '') {
            $mapIframeSrc = $mapsEmbedKey !== ''
                ? 'https://www.google.com/maps/embed/v1/place?key='.rawurlencode($mapsEmbedKey).'&q='.rawurlencode($mapQuery)
                : 'https://www.google.com/maps?q='.rawurlencode($mapQuery).'&output=embed';
        } else {
            $mapIframeSrc = '';
        }

        $priceFormatted = \lh_format_money((float) $property['price'], 0);
        $locationLineParts = array_filter([
            trim((string) ($property['city'] ?? '')),
            trim((string) ($property['address'] ?? '')),
        ]);
        $locationLine = $locationLineParts ? implode(' · ', $locationLineParts) : '';

        return view('pages.properties.show', [
            'pageTitle' => $lhPropTitleRaw.' — Like HOME',
            'pageDescription' => $pageDescription,
            'canonicalUrl' => $canonicalUrl,
            'lhLocaleAlternateUrls' => $lhLocaleAlternateUrls,
            'ogImage' => $ogImage,
            'ogType' => 'website',
            'lhJsonLd' => $lhJsonLd,
            'property' => $property,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'checkIn' => $check_in,
            'checkOut' => $check_out,
            'guests' => $guests,
            'has_checkin' => $has_checkin,
            'has_checkout' => $has_checkout,
            'images' => $images,
            'propertyIdForImages' => $propertyIdForImages,
            'lhPropTitleRaw' => $lhPropTitleRaw,
            'locationLine' => $locationLine,
            'amenities' => $amenities,
            'mapIframeSrc' => $mapIframeSrc,
            'priceFormatted' => $priceFormatted,
            'same_area_properties' => $sameAreaProperties,
            'same_area_see_more_url' => $sameAreaSeeMoreUrl,
            'same_area_label' => $sameAreaLabel,
            'lhPdFpJs' => match (app()->getLocale()) {
                'en' => 'en',
                'ru' => 'ru',
                default => 'ro',
            },
        ]);
    }
}
