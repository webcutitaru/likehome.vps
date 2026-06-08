@php
    \App\Legacy\LegacyBridge::boot();
@endphp

<link rel="stylesheet" href="{{ asset('assets/css/tailwind.build.css') }}">

<style>
    .lh-cal-page .bg-cta { background-color: rgb(74 103 65); }
    .lh-cal-page .text-cta { color: rgb(74 103 65); }
    .lh-cal-page .ring-cta { --tw-ring-color: rgb(74 103 65); }
    .lh-cal-page .bg-brand-100 { background-color: rgb(238 241 244); }
    .lh-cal-page .bg-brand-50 { background-color: rgb(248 249 251); }
    .lh-cal-page .hover\:bg-brand-100\/60:hover { background-color: rgb(238 241 244 / 0.6); }
    .lh-cal-page .hover\:bg-brand-50:hover { background-color: rgb(248 249 251); }
    .lh-cal-page .text-ink { color: rgb(45 47 52); }
    .lh-cal-page .cal-sticky-header { background: rgb(248 250 252 / 0.95); backdrop-filter: blur(4px); }
    .lh-cal-page .lh-cal-root {
        min-height: 70vh;
        --cal-cell: 44px;
    }
    .lh-cal-page #calCalendarRoot {
        min-height: 60vh;
    }
</style>

<div class="lh-cal-page" wire:ignore>
    <div class="lh-cal-root">
        @include('filament.pages.property-calendar-body')
    </div>
    @include('filament.partials.booking-detail-modal')
</div>
