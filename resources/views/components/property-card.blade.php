@php
    \App\Legacy\LegacyBridge::boot();
    $checkIn = $checkIn ?? '';
    $checkOut = $checkOut ?? '';
    $guests = $guests ?? '';
    $query = array_filter(['check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests]);
    $url = \App\Support\WebUrls::propertyShow($property, null, $query);
    $images = !empty($property['image_name']) ? explode(',', (string) $property['image_name']) : [];
    $pid = (int) ($property['id'] ?? 0);
    $slideUrls = [];
    foreach ($images as $rawName) {
        $bn = trim((string) $rawName);
        if ($bn !== '') {
            $slideUrls[] = lh_property_image_url($pid, $bn, 'thumb');
        }
    }
    $firstImage = !empty($images[0]) ? trim($images[0]) : null;
    $imgSrc = $firstImage
        ? lh_property_image_url($pid, $firstImage, 'thumb')
        : 'https://placehold.co/600x400/e2e8f0/94a3b8?text=' . rawurlencode(__('card.no_image'));
    $title = $property['title'] ?? __('card.fallback_title');
    $cityRaw = trim((string) ($property['city'] ?? ''));
    $districtRaw = trim((string) ($property['district'] ?? ''));
    $locParts = [];
    if ($cityRaw !== '') {
        $locParts[] = $cityRaw;
    }
    if ($districtRaw !== '' && mb_strtolower($districtRaw, 'UTF-8') !== mb_strtolower($cityRaw, 'UTF-8')) {
        $locParts[] = lh_location_label($districtRaw);
    }
    $displayLoc = $locParts !== [] ? implode(' • ', $locParts) : trim((string) ($property['location'] ?? ''));
    $sleepCap = (int) ($property['sleep_capacity'] ?? 0);
    $price = lh_format_money((float) ($property['price'] ?? 0), 0);
    $propertyType = (string) ($property['property_type'] ?? '');
@endphp
<article class="group/card bg-white rounded-2xl border border-black/[0.08] shadow-lg shadow-black/[0.04] hover:shadow-2xl hover:shadow-black/[0.12] hover:-translate-y-1 overflow-hidden transition-all duration-300 flex flex-col">

  <div
    class="lh-property-card-media relative aspect-[4/3] overflow-hidden rounded-t-2xl bg-surface-2 group block"
    @if(count($slideUrls) >= 2) data-lh-slide-urls="{{ json_encode($slideUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" @endif
  >
    <img
      src="{{ $imgSrc }}"
      alt="{{ $title }}"
      class="lh-property-card-slide-img absolute inset-0 z-0 h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
      loading="lazy"
      decoding="async"
    >
    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[3] h-24 bg-gradient-to-t from-ink/25 to-transparent"></div>
    <a
      href="{{ $url }}"
      class="absolute inset-0 z-[8] rounded-t-2xl outline-none ring-0"
      aria-label="{{ $title }} — {{ __('card.open_details') }}"
    ></a>
  </div>

  <div class="p-5 flex flex-col flex-1">

    <div class="flex items-center justify-between gap-2 mb-2">
      @if($propertyType !== '')
        <span class="text-[10px] font-bold uppercase tracking-widest text-ink/80 bg-brand-100 px-2.5 py-1 rounded-full border border-black/[0.06]">{{ $propertyType }}</span>
      @endif

      @if($displayLoc !== '')
        <span class="text-xs text-blue-grey flex items-center gap-1 ml-auto min-w-0">
          <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span class="truncate">{{ $displayLoc }}</span>
        </span>
      @endif
    </div>

    <h3 class="font-semibold text-ink text-lg tracking-tight leading-snug mb-3">
      <a href="{{ $url }}" class="hover:text-cta transition-colors">{{ $title }}</a>
    </h3>

    <div class="flex items-center gap-3 text-xs text-blue-grey mb-4">
      @if($sleepCap > 0)
        <span class="flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ $sleepCap }} {{ $sleepCap === 1 ? __('card.guest_one') : __('card.guests') }}
        </span>
      @endif

      @if(!empty($property['rooms']))
        <span class="flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          {{ (int) $property['rooms'] }} {{ __('card.rooms') }}
        </span>
      @endif
    </div>

    <div class="mt-auto flex items-center justify-between gap-3">
      <div>
        <span class="text-xs font-semibold text-blue-grey">{{ __('card.from') }} </span>
        <span class="text-xl font-bold text-ink tracking-tight">{{ $price }}</span>
      </div>

      <a
        href="{{ $url }}"
        class="inline-flex items-center gap-1.5 bg-cta hover:brightness-110 text-white text-xs font-semibold px-4 py-2.5 rounded-2xl transition-all shadow-md shadow-black/10"
      >
        {{ __('card.view_property') }}
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </a>
    </div>

  </div>
</article>
