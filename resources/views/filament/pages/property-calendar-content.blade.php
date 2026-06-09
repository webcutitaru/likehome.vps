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
    .lh-cal-page {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .lh-cal-page .lh-cal-root {
        --cal-cell: 44px;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }
    .lh-cal-page .lh-cal-shell {
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1;
        overflow: hidden;
    }
    .lh-cal-page .lh-cal-intro > div:last-child {
        align-self: stretch;
    }
    .lh-cal-page .cal-sticky-header {
        background: rgb(248 250 252 / 0.95);
        backdrop-filter: blur(4px);
    }
    /* Calendar layout fallbacks (ported from assets/css/style.css) */
    .lh-cal-page #calCalendarRoot {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-width: 0;
        min-height: 0;
        flex: 1 1 0%;
        overflow: hidden;
    }
    .lh-cal-page #calVertScroll {
        box-sizing: border-box;
        flex: 1 1 0%;
        min-width: 0;
        min-height: 0;
        width: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-gutter: stable;
    }
    .lh-cal-page #calVertScroll > .flex {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        width: 100%;
        min-width: 0;
        align-items: stretch;
        box-sizing: border-box;
    }
    .lh-cal-page #calDateHeaderHScroll {
        min-width: 0;
        overflow-x: auto;
        overflow-y: visible;
        box-sizing: border-box;
        scrollbar-gutter: stable;
    }
    .lh-cal-page .cal-sticky-header,
    .lh-cal-page .cal-date-header-sticky {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        /* Sub Filament topbar/sidebar (z-index 30) — altfel meniul hamburger se suprapune */
        z-index: 10;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .lh-cal-page #calGridHScroll {
        position: relative;
        z-index: 0;
        width: 100%;
        min-width: 0;
        min-height: 0;
        flex-shrink: 0;
        align-self: stretch;
        overflow-x: auto;
        box-sizing: border-box;
        scrollbar-gutter: stable;
    }
    .lh-cal-page #calVertScroll .cal-grid-row,
    .lh-cal-page #calVertScroll .cal-prop-row {
        height: 60px;
        min-height: 60px;
        max-height: 60px;
        box-sizing: border-box;
    }
    .lh-cal-page .cal-prop-col,
    .lh-cal-page .cal-prop-search-col {
        width: 16rem;
        box-sizing: border-box;
    }
    .lh-cal-page .cal-header-prop {
        height: 76px;
        min-height: 76px;
    }
    .lh-cal-page .cal-date-header {
        height: 76px;
        min-height: 76px;
    }
    .lh-cal-page .cal-month-row {
        height: 38px;
    }
    .lh-cal-page .cal-day-row {
        height: 38px;
        min-height: 38px;
    }
    .lh-cal-page .cal-h-scroll {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }
    /*
     * tailwind.build.css (încărcat pentru grid) poate strica translate-ul sidebar-ului Filament.
     * Topbar (31) trebuie deasupra sidebar-ului (30) ca butonul X să funcționeze pe mobil.
     */
    @media (max-width: 1023px) {
        .fi-sidebar:not(.fi-sidebar-open) {
            transform: translate3d(-100%, 0, 0) !important;
        }
        html[dir="rtl"] .fi-sidebar:not(.fi-sidebar-open) {
            transform: translate3d(100%, 0, 0) !important;
        }
        .fi-sidebar.fi-sidebar-open {
            transform: translate3d(0, 0, 0) !important;
        }
    }
    .fi-sidebar-close-overlay {
        z-index: 29;
    }
    .fi-sidebar {
        z-index: 30;
    }
    .fi-topbar-ctn {
        z-index: 31;
    }
    @media (max-width: 767px) {
        .fi-page-content:has(.lh-cal-page) {
            padding-inline: 0.5rem;
        }
        .lh-cal-page .lh-cal-intro h2 {
            font-size: 1.125rem;
            line-height: 1.3;
        }
        .lh-cal-page .lh-cal-intro > div:first-child p {
            display: none;
        }
        .lh-cal-page #lhCalOptionsToggle {
            width: 100%;
            justify-content: center;
        }
        .lh-cal-page .lh-cal-intro > div:first-child {
            width: 100%;
        }
        .lh-cal-page .cal-prop-col,
        .lh-cal-page .cal-prop-search-col {
            width: var(--cal-prop-col, 7.25rem);
            position: sticky;
            left: 0;
            z-index: 5;
        }
        .lh-cal-page .cal-prop-search-col {
            z-index: 6;
        }
        .lh-cal-page .cal-prop-lot {
            display: none;
        }
        .lh-cal-page .cal-day-dow {
            display: none;
        }
        .lh-cal-page .cal-header-prop {
            height: 58px;
            min-height: 58px;
            padding: 0.375rem 0.5rem;
        }
        .lh-cal-page .cal-date-header {
            height: 58px;
            min-height: 58px;
        }
        .lh-cal-page .cal-month-row {
            height: 24px;
            font-size: 9px;
        }
        .lh-cal-page .cal-day-row {
            height: 34px;
            min-height: 34px;
            font-size: 9px;
        }
        .lh-cal-page #calVertScroll .cal-grid-row,
        .lh-cal-page #calVertScroll .cal-prop-row {
            height: 48px;
            min-height: 48px;
            max-height: 48px;
        }
        .lh-cal-page .cal-prop-row {
            padding-left: 0.5rem;
            padding-right: 0.375rem;
        }
        .lh-cal-page .cal-cell-blocked {
            font-size: 7px;
            letter-spacing: -0.02em;
        }
        .lh-cal-page #calCalendarRoot {
            border-radius: 1rem;
        }
        .lh-cal-page .cal-cell-booking {
            font-size: 8px;
        }
    }
</style>

<div class="lh-cal-page" wire:ignore>
    <div class="lh-cal-root">
        @include('filament.pages.property-calendar-body')
    </div>
    @include('filament.partials.booking-detail-modal')
</div>
