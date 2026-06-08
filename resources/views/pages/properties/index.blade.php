@extends('layouts.app')

@section('content')
@php
  $checkIn = request('check_in', '');
  $checkOut = request('check_out', '');
  $guests = request('guests', '');
  $propCountLabel = $propertyCount === 1
    ? '1 ' . __('page.properties.count_one')
    : __('page.properties.count_many', ['n' => (string) $propertyCount]);
  $filterHint = __('page.properties.filter_hint');
  $heroDesc = ($lhAreaDistrict !== '' || $lhAreaCity !== '')
    ? __('page.properties.hero_in_area', ['count' => $propCountLabel, 'hint' => $filterHint])
    : __('page.properties.hero_browse', ['count' => $propCountLabel, 'hint' => $filterHint]);
  $areaSubtitle = $lhAreaDistrict !== ''
    ? lh_location_label($lhAreaDistrict)
    : ($lhAreaCity !== '' ? lh_location_label($lhAreaCity) : '');
@endphp

<div class="bg-gradient-to-br from-logo via-brand-700 to-ink relative pt-12 md:pt-16">
  <div class="max-w-4xl mx-auto px-4 text-center font-sans pb-6 md:pb-8 lg:pb-36">
    <h1 class="text-3xl md:text-5xl font-bold text-white mb-3">{{ $heroTitle }}</h1>
    @if($areaSubtitle !== '')
    <p class="text-white text-lg md:text-xl font-semibold mb-2">{{ $areaSubtitle }}</p>
    @endif
    <p class="text-white/70 text-base md:text-lg max-w-2xl mx-auto">{{ $heroDesc }}</p>
  </div>
  <div class="relative z-10 flex justify-center px-3 sm:px-4 min-w-0 pb-8 md:pb-8 lg:absolute lg:inset-x-0 lg:bottom-0 lg:translate-y-1/2 lg:pb-0">
    <div class="w-full min-w-0 max-w-6xl">
      @include('components.search-bar', [
        'properties' => collect(),
        'lhSearchBarShowSector' => $lhSearchBarShowSector ?? true,
        'lhAreaDistrict' => $lhAreaDistrict,
        'lhAreaCity' => $lhAreaCity,
        'lhSectorOptions' => $lhSectorOptions,
      ])
    </div>
  </div>
</div>

<div class="max-w-6xl mx-auto px-4 pb-0 lg:pt-20"></div>

<div id="search-results-section" class="max-w-6xl mx-auto px-4 mt-0 pt-0 pb-0 scroll-mt-28 md:scroll-mt-24">
  <div id="results-header" class="hidden mt-6 mb-6">
    <h2 class="text-xl font-semibold text-ink text-center">{{ __('search.available_properties') }}</h2>
  </div>
  <div
    id="results-container"
    class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-300 ease-out opacity-0"
  ></div>
</div>

<div class="max-w-6xl mx-auto px-4 mt-8 lg:mt-0 pt-0 pb-0">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
    @forelse($properties as $property)
      <x-property-card
        :property="$property"
        :check-in="$checkIn"
        :check-out="$checkOut"
        :guests="$guests"
      />
    @empty
      <p class="col-span-full text-blue-grey">{{ __('page.index.empty') }}</p>
    @endforelse
  </div>
</div>
@endsection
