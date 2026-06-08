@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-14 md:py-20">
  <div class="bg-white border border-black/10 rounded-2xl p-8 sm:p-10 shadow-sm">
    <div class="flex items-start gap-4 mb-6">
      <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </span>
      <div>
        <h1 class="text-2xl font-black text-ink tracking-tight">{{ __('payment.failed.heading') }}</h1>
        <p class="text-sm text-blue-grey mt-2">{{ __('payment.failed.subtitle') }}</p>
      </div>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <a href="javascript:history.back()" class="inline-flex justify-center items-center px-5 py-3 rounded-xl font-bold bg-cta text-white hover:brightness-110 transition-all">{{ __('payment.failed.retry') }}</a>
      <a href="{{ \App\Support\WebUrls::page('contact') }}" class="inline-flex justify-center items-center px-5 py-3 rounded-xl font-bold border-2 border-black/10 text-ink hover:bg-brand-50 transition-colors">{{ __('payment.failed.contact') }}</a>
    </div>
  </div>
</div>
@endsection
