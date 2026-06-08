@php
    \App\Legacy\LegacyBridge::boot();
    $lhSearchBarShowSector = !empty($lhSearchBarShowSector);
    $properties = $properties ?? [];
    $lhAreaDistrict = trim((string) ($lhAreaDistrict ?? ''));
    $lhAreaCity = trim((string) ($lhAreaCity ?? ''));
    $lhSectorOptions = is_array($lhSectorOptions ?? null) ? $lhSectorOptions : [];
    $selProperty = request('property_id', '');
    $selCheckin = request('check_in', '');
    $selCheckout = request('check_out', '');
    $selGuests = request('guests', '');
    $lhSectorChoices = $lhSectorOptions;
    if ($lhSearchBarShowSector && $lhAreaDistrict !== '' && !in_array($lhAreaDistrict, $lhSectorChoices, true)) {
        $lhSectorChoices[] = $lhAreaDistrict;
        sort($lhSectorChoices, SORT_STRING);
    }
@endphp
<section class="w-full min-w-0 max-w-full overflow-x-clip bg-white border border-black/[0.04] shadow-xl shadow-black/5 rounded-[1.5rem] sm:rounded-[2rem] px-3 py-4 sm:px-4 sm:py-5 md:px-6 md:py-6" id="search-bar">
  @if(!$lhSearchBarShowSector)
    @if($lhAreaDistrict !== '')
    <input type="hidden" id="lh-filter-district" name="lh_filter_district" value="{{ $lhAreaDistrict }}">
    @elseif($lhAreaCity !== '')
    <input type="hidden" id="lh-filter-city" name="lh_filter_city" value="{{ $lhAreaCity }}">
    @endif
  @elseif($lhAreaCity !== '' && $lhAreaDistrict === '')
  <input type="hidden" id="lh-filter-city" name="lh_filter_city" value="{{ $lhAreaCity }}">
  @endif
  <div class="flex w-full min-w-0 flex-col gap-3 sm:gap-4 lg:flex-row lg:items-stretch lg:gap-4">

    @if($lhSearchBarShowSector)
    <div class="flex-1 min-w-0 lg:max-w-[14rem] lg:shrink-0">
      <div id="lh-search-sector-label" class="block text-xs font-semibold text-blue-grey uppercase tracking-wide mb-1">{{ __('search.sector') }}</div>
      <div class="relative">
        <select
          id="lh-sector-select"
          name="lh_sector"
          aria-labelledby="lh-search-sector-label"
          class="w-full min-h-[3rem] h-12 min-w-0 box-border appearance-none bg-surface border border-black/8 rounded-2xl px-4 py-2 pr-10 text-ink text-base md:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cta/20 focus:border-cta transition cursor-pointer shadow-sm"
        >
          <option value="" @selected($lhAreaDistrict === '')>{{ __('search.all_sectors') }}</option>
          @foreach($lhSectorChoices as $sector)
            <option value="{{ $sector }}" @selected($lhAreaDistrict === $sector)>{{ lh_location_label($sector) }}</option>
          @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
          <svg class="w-4 h-4 text-blue-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>
    </div>
    <input type="hidden" id="lh-property-id-all" name="property_id" value="all">
    @endif

    @if(!$lhSearchBarShowSector)
    <div class="flex-1 min-w-0">
      <div id="lh-search-property-label" class="block text-xs font-semibold text-blue-grey uppercase tracking-wide mb-1">{{ __('search.property') }}</div>
      <div class="relative">
        <select
          id="property-select"
          name="property_id"
          aria-labelledby="lh-search-property-label"
          class="w-full min-h-[3rem] h-12 min-w-0 box-border appearance-none bg-surface border border-black/8 rounded-2xl px-4 py-2 pr-10 text-ink text-base md:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cta/20 focus:border-cta transition cursor-pointer shadow-sm"
        >
          <option value="all" @selected($selProperty === 'all' || $selProperty === '')>{{ __('search.all_properties') }}</option>
          @foreach($properties as $prop)
            @php
              $propId = is_array($prop) ? ($prop['id'] ?? 0) : $prop->id;
              $propSlug = is_array($prop) ? ($prop['slug'] ?? '') : $prop->slug;
              $propTitle = is_array($prop) ? ($prop['title'] ?? '') : $prop->title;
            @endphp
            <option
              value="{{ (int) $propId }}"
              data-slug="{{ $propSlug }}"
              @selected((string) $selProperty === (string) $propId)
            >{{ $propTitle }}</option>
          @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
          <svg class="w-4 h-4 text-blue-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>
    </div>
    @endif

    <div class="flex-1 min-w-0">
      <div id="lh-search-checkin-label" class="block text-xs font-semibold text-blue-grey uppercase tracking-wide mb-1">{{ __('search.check_in') }}</div>
      <div class="relative">
        <input
          type="text"
          id="check-in"
          name="check_in"
          aria-labelledby="lh-search-checkin-label"
          placeholder="{{ __('search.select_date') }}"
          readonly
          value="{{ $selCheckin }}"
          class="w-full min-h-[3rem] min-w-0 bg-surface border border-black/8 rounded-2xl px-4 py-3 pl-10 text-ink text-base md:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cta/20 focus:border-cta transition cursor-pointer shadow-sm box-border"
        >
        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
          <svg class="w-4 h-4 text-blue-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="flex-1 min-w-0">
      <div id="lh-search-checkout-label" class="block text-xs font-semibold text-blue-grey uppercase tracking-wide mb-1">{{ __('search.check_out') }}</div>
      <div class="relative">
        <input
          type="text"
          id="check-out"
          name="check_out"
          aria-labelledby="lh-search-checkout-label"
          placeholder="{{ __('search.select_date') }}"
          readonly
          value="{{ $selCheckout }}"
          class="w-full min-h-[3rem] min-w-0 bg-surface border border-black/8 rounded-2xl px-4 py-3 pl-10 text-ink text-base md:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cta/20 focus:border-cta transition cursor-pointer shadow-sm box-border"
        >
        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
          <svg class="w-4 h-4 text-blue-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="w-full min-w-0 lg:w-36 lg:shrink-0">
      <div id="lh-search-guests-label" class="block text-xs font-semibold text-blue-grey uppercase tracking-wide mb-1">{{ __('search.guests') }}</div>
      <div class="relative">
        <select
          id="guests-select"
          name="guests"
          aria-labelledby="lh-search-guests-label"
          class="w-full min-h-[3rem] h-12 min-w-0 box-border appearance-none bg-surface border border-black/8 rounded-2xl px-4 py-2 pr-10 text-ink text-base md:text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cta/20 focus:border-cta transition cursor-pointer shadow-sm"
        >
          <option value="1" @selected($selGuests === '1')>{{ __('booking.guest_one') }}</option>
          <option value="2" @selected($selGuests === '2')>{{ __('booking.guest_many', ['n' => '2']) }}</option>
          <option value="3" @selected($selGuests === '3')>{{ __('booking.guest_many', ['n' => '3']) }}</option>
          <option value="4" @selected($selGuests === '4')>{{ __('booking.guest_many', ['n' => '4']) }}</option>
          <option value="5" @selected($selGuests === '5')>{{ __('booking.guest_many', ['n' => '5']) }}</option>
          <option value="6" @selected($selGuests === '6')>{{ __('booking.guest_six_plus') }}</option>
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
          <svg class="w-4 h-4 text-blue-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="w-full min-w-0 lg:w-auto lg:shrink-0 flex flex-col justify-end">
      <div class="block text-xs font-semibold text-transparent uppercase tracking-wide mb-1 select-none max-lg:hidden">
        &nbsp;
      </div>
      <button
        id="search-btn"
        type="button"
        class="w-full lg:w-auto inline-flex items-center justify-center gap-2 min-h-[3rem] h-12 px-6 sm:px-8 bg-cta hover:brightness-110 active:brightness-95 text-white font-semibold text-base md:text-sm rounded-full shadow-md shadow-black/10 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-cta/25 focus:ring-offset-2 focus:ring-offset-white lg:whitespace-nowrap"
        data-mode="search"
      >
        <svg id="btn-icon-search" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <svg id="btn-icon-reserve" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="search-btn-text">{{ __('search.search') }}</span>
      </button>
    </div>

  </div>

  <p class="text-xs text-blue-grey mt-2 px-0.5 leading-snug">{{ __('search.min_night_hint') }}</p>
  <p id="lh-search-date-error" class="hidden mt-1 text-xs font-medium text-red-800 px-0.5" role="alert"></p>

  <div id="search-loading" class="hidden mt-4 flex items-center gap-2 text-sm text-blue-grey">
    <svg class="animate-spin w-4 h-4 text-cta" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
    </svg>
    {{ __('search.loading') }}
  </div>

</section>
