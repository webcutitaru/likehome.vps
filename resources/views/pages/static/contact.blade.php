@extends('layouts.app')

@section('content')
@php
    \App\Legacy\LegacyBridge::boot();
    $contactEmail = lh_site_contact_email();
    $contactCity = lh_site_contact_city();
@endphp
<div class="border-b border-black/[0.06] bg-white/60">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-10 md:pt-14 pb-6 md:pb-8">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-grey mb-3">{{ $meta['label'] }}</p>
    <h1 class="text-3xl md:text-4xl font-bold text-ink tracking-tight">{{ $meta['heading'] }}</h1>
    <p class="mt-4 text-base text-blue-grey leading-relaxed max-w-xl">{{ $sections['intro'] ?? '' }}</p>
  </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 mt-6 md:mt-10 pt-0 pb-0">
  <div class="grid md:grid-cols-2 gap-8 md:gap-10">
    <a href="mailto:{{ $contactEmail }}" class="group rounded-3xl border border-black/[0.08] bg-white p-8 shadow-sm shadow-black/[0.04] hover:border-black/15 hover:shadow-md transition-all">
      <div class="text-xs font-semibold uppercase tracking-widest text-blue-grey mb-2">{{ $sections['write_label'] ?? '' }}</div>
      <div class="text-lg font-semibold text-ink group-hover:underline decoration-black/20 underline-offset-4">{{ $contactEmail }}</div>
      <p class="mt-3 text-sm text-blue-grey leading-relaxed">{{ $sections['write_hint'] ?? '' }}</p>
    </a>
    <div class="rounded-3xl border border-black/[0.08] bg-surface p-8 shadow-sm shadow-black/[0.04]">
      <div class="text-xs font-semibold uppercase tracking-widest text-blue-grey mb-2">{{ $sections['location_label'] ?? '' }}</div>
      <div class="text-lg font-semibold text-ink">{{ $contactCity }}</div>
      <p class="mt-3 text-sm text-blue-grey leading-relaxed">{{ $sections['location_hint'] ?? '' }}</p>
    </div>
  </div>

  <div class="mt-10 rounded-2xl bg-black/[0.03] border border-black/[0.06] px-6 py-5 text-sm text-blue-grey text-center md:text-left">
    <span class="font-semibold text-ink">{{ $sections['response_label'] ?? '' }}</span> {{ $sections['response_body'] ?? '' }}
  </div>
</div>
@endsection
