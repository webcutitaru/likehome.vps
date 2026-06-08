@extends('layouts.app')

@section('content')
@php
    $premiumItems = is_array($sections['premium_items'] ?? null) ? $sections['premium_items'] : [];
    $ownersItems = is_array($sections['owners_items'] ?? null) ? $sections['owners_items'] : [];
@endphp
<div class="border-b border-black/[0.06] bg-white/60">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-10 md:pt-14 pb-6 md:pb-8">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-grey mb-3">{{ $meta['label'] }}</p>
    <h1 class="text-3xl md:text-4xl font-bold text-ink tracking-tight">{{ $meta['heading'] }}</h1>
    <p class="mt-4 text-lg text-ink/80 leading-relaxed max-w-2xl">{{ $sections['intro'] ?? '' }}</p>
  </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 mt-6 md:mt-10 pt-0 pb-0 space-y-16">
  <section class="grid md:grid-cols-2 gap-10 md:gap-14 items-start">
    <div>
      <h2 class="text-xl font-bold text-ink mb-4">{{ $sections['mission_title'] ?? '' }}</h2>
      <p class="text-blue-grey leading-relaxed">{{ $sections['mission_body'] ?? '' }}</p>
    </div>
    <div class="rounded-3xl bg-gradient-to-br from-logo/90 to-ink p-8 md:p-10 text-white shadow-xl shadow-black/15">
      <h2 class="text-lg font-bold mb-3">{{ $sections['premium_title'] ?? '' }}</h2>
      <ul class="space-y-3 text-sm text-white/85 leading-relaxed">
        @foreach($premiumItems as $item)
          <li class="flex gap-3"><span class="text-white/50">·</span> {{ $item }}</li>
        @endforeach
      </ul>
    </div>
  </section>

  <section class="rounded-3xl border border-black/[0.08] bg-white/90 p-8 md:p-10 shadow-sm shadow-black/[0.04]">
    <h2 class="text-xl font-bold text-ink mb-6">{{ $sections['trust_title'] ?? '' }}</h2>
    <div class="grid sm:grid-cols-3 gap-8 text-center sm:text-left">
      <div>
        <div class="text-2xl font-bold text-ink mb-1">{{ $sections['stat1_num'] ?? '' }}</div>
        <div class="text-sm text-blue-grey leading-snug">{{ $sections['stat1_label'] ?? '' }}</div>
      </div>
      <div>
        <div class="text-2xl font-bold text-ink mb-1">{{ $sections['stat2_num'] ?? '' }}</div>
        <div class="text-sm text-blue-grey leading-snug">{{ $sections['stat2_label'] ?? '' }}</div>
      </div>
      <div>
        <div class="text-2xl font-bold text-ink mb-1">{{ $sections['stat3_num'] ?? '' }}</div>
        <div class="text-sm text-blue-grey leading-snug">{{ $sections['stat3_label'] ?? '' }}</div>
      </div>
    </div>
  </section>

  <section class="rounded-3xl border border-black/[0.08] bg-white/90 p-8 md:p-10 shadow-sm shadow-black/[0.04]">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-grey mb-3">{{ $sections['owners_label'] ?? '' }}</p>
    <h2 class="text-xl font-bold text-ink mb-4">{{ $sections['owners_title'] ?? '' }}</h2>
    <p class="text-blue-grey leading-relaxed max-w-2xl mb-5">{{ $sections['owners_body'] ?? '' }}</p>
    <ul class="space-y-3 text-sm text-blue-grey leading-relaxed max-w-2xl mb-8">
      @foreach($ownersItems as $item)
        <li class="flex gap-3"><span class="text-ink/35 shrink-0">·</span> {{ $item }}</li>
      @endforeach
    </ul>
    <a href="{{ \App\Support\WebUrls::page('contact') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl text-sm font-semibold bg-cta text-white shadow-md shadow-black/10 hover:brightness-110 transition-all">
      {{ $sections['owners_cta'] ?? '' }}
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </section>

  <p class="text-center text-sm text-blue-grey">
    {{ $sections['footer_text'] ?? '' }}
    <a href="{{ \App\Support\WebUrls::page('contact') }}" class="text-ink font-semibold underline decoration-black/20 underline-offset-4 hover:decoration-ink">{{ $sections['footer_contact'] ?? '' }}</a>
    ·
    <a href="{{ \App\Support\WebUrls::propertiesIndex() }}" class="text-ink font-semibold underline decoration-black/20 underline-offset-4 hover:decoration-ink">{{ $sections['footer_properties'] ?? '' }}</a>
  </p>
</div>
@endsection
