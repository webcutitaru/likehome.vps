@extends('layouts.app')

@section('content')
@php
    \App\Legacy\LegacyBridge::boot();
    $pageSections = is_array($sections['sections'] ?? null) ? $sections['sections'] : [];
@endphp
<div class="border-b border-black/[0.06] bg-white/60">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-10 md:pt-14 pb-6 md:pb-8">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-grey mb-3">{{ $sections['label'] ?? '' }}</p>
    <h1 class="text-3xl md:text-4xl font-bold text-ink tracking-tight">{{ __('page.terms.heading') }}</h1>
    <p class="mt-4 text-sm text-blue-grey">
      {{ __('page.terms.updated') }} {{ date('d.m.Y') }}.
      {{ $sections['updated_note'] ?? '' }}
    </p>
  </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 mt-6 md:mt-10 pt-0 pb-0">
  <div class="prose prose-neutral max-w-none space-y-10 text-blue-grey leading-relaxed [&_h2]:text-ink [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-0 [&_h2]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2">
    @if(!empty($sections['intro']))
      <div class="text-blue-grey leading-relaxed">{!! lh_terms_replace_placeholders((string) $sections['intro']) !!}</div>
    @endif
    @foreach($pageSections as $section)
      @if(!is_array($section))
        @continue
      @endif
      <section>
        <h2>{{ $section['title'] ?? '' }}</h2>
        {!! lh_terms_replace_placeholders((string) ($section['body'] ?? '')) !!}
      </section>
    @endforeach
  </div>
</div>
@endsection
