@php
    \App\Legacy\LegacyBridge::boot();
    $lhSeoTitle = $pageTitle ?? 'Like HOME';
    $lhSeoDesc = $pageDescription ?? '';
    $lhSeoCanonical = $canonicalUrl ?? url('/');
    if ($lhSeoCanonical === '' || $lhSeoCanonical === '/') {
        $lhSeoCanonical = \lh_seo_fallback_canonical();
    }
    $lhSeoOgTitle = $ogTitle ?? $lhSeoTitle;
    $lhSeoOgDesc = $ogDescription ?? ($lhSeoDesc !== '' ? $lhSeoDesc : $lhSeoTitle);
    $lhSeoOgType = $ogType ?? 'website';
    $lhSeoOgImage = $ogImage ?? '';
    $lhSeoOgLocale = $ogLocale ?? \lh_locale_og_tag();
    $lhSeoTwitterCard = $twitterCard ?? ($lhSeoOgImage !== '' ? 'summary_large_image' : 'summary');
    $lhSeoRobots = $robotsMeta ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $lhAlternateUrls = (isset($lhLocaleAlternateUrls) && is_array($lhLocaleAlternateUrls))
        ? $lhLocaleAlternateUrls
        : \lh_locale_alternate_urls();
    $lhFooterEmail = \lh_site_contact_email();
    $lhFooterCity = \lh_site_contact_city();
    $navItems = \App\Support\WebUrls::navItems();
    $langItems = \App\Support\WebUrls::localeSwitcherItems();
    $langCurrent = collect($langItems)->firstWhere('current', true) ?? ($langItems[0] ?? null);
@endphp
<!DOCTYPE html>
<html lang="{{ \lh_locale_html_lang() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $lhSeoTitle }}</title>
  <meta name="robots" content="{{ $lhSeoRobots }}">
  @if($lhSeoDesc !== '')
  <meta name="description" content="{{ $lhSeoDesc }}">
  @endif
  <link rel="canonical" href="{{ $lhSeoCanonical }}">
  @foreach($lhAlternateUrls as $lhHrefLang => $lhAltHref)
  <link rel="alternate" hreflang="{{ $lhHrefLang }}" href="{{ $lhAltHref }}">
  @endforeach
  @if(isset($lhAlternateUrls['ro']))
  <link rel="alternate" hreflang="x-default" href="{{ $lhAlternateUrls['ro'] }}">
  @endif
  <meta name="theme-color" content="#1e3a5f">
  <meta property="og:site_name" content="Like HOME">
  <meta property="og:locale" content="{{ $lhSeoOgLocale }}">
  <meta property="og:type" content="{{ $lhSeoOgType }}">
  <meta property="og:title" content="{{ $lhSeoOgTitle }}">
  <meta property="og:description" content="{{ $lhSeoOgDesc }}">
  <meta property="og:url" content="{{ $lhSeoCanonical }}">
  @if($lhSeoOgImage !== '')
  <meta property="og:image" content="{{ $lhSeoOgImage }}">
  @endif
  <meta name="twitter:card" content="{{ $lhSeoTwitterCard }}">
  <meta name="twitter:title" content="{{ $lhSeoOgTitle }}">
  <meta name="twitter:description" content="{{ $lhSeoOgDesc }}">
  @if($lhSeoOgImage !== '')
  <meta name="twitter:image" content="{{ $lhSeoOgImage }}">
  @endif
  <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
  <link rel="stylesheet" href="{{ asset('assets/css/tailwind.build.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <style>
    .site-shell { background: radial-gradient(circle at top left, rgb(var(--color-logo) / 0.06), transparent 28%), radial-gradient(circle at top right, rgb(var(--color-blue-grey) / 0.08), transparent 24%), linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%); }
    .premium-header-blur { backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    @media (max-width: 767px) {
      .touch-scroll-x { -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; }
    }
    .mobile-drawer-open { overflow: hidden; }
    #site-header.is-scrolled { box-shadow: 0 8px 28px rgb(0 0 0 / 0.14); }
    .lh-lang-switcher__menu { transform-origin: top right; transform: translateY(-4px) scale(0.98); opacity: 0; transition: opacity 0.15s ease, transform 0.15s ease; }
    .lh-lang-switcher__menu--open { transform: translateY(0) scale(1); opacity: 1; }
  </style>
  @isset($lhJsonLd)
  <script type="application/ld+json">{!! $lhJsonLd !!}</script>
  @endisset
  @php require base_path('app/Legacy/includes/clarity.php'); @endphp
  @php require base_path('app/Legacy/includes/gtag.php'); @endphp
  @stack('head')
</head>
<body class="site-shell text-ink font-sans antialiased min-h-screen">
<header id="site-header" class="sticky top-0 z-50 border-b border-white/10 bg-logo text-white transition-shadow duration-300">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="relative flex items-center min-h-[4.25rem] py-2 gap-4 lg:gap-6">
      <div class="flex flex-1 min-w-0 justify-start">
        <a href="{{ \App\Support\WebUrls::home() }}" class="flex items-center gap-2.5 group shrink-0 min-w-0" aria-label="{{ __('nav.home_aria') }}">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 shadow-sm shadow-black/10">
            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          </span>
          <span class="flex flex-col justify-center leading-tight text-white min-w-0">
            <span class="text-[13px] font-medium tracking-tight">Like</span>
            <span class="text-[13px] font-bold tracking-wide uppercase">HOME</span>
          </span>
        </a>
      </div>
      <nav class="hidden lg:flex absolute left-1/2 top-1/2 z-10 -translate-x-1/2 -translate-y-1/2 items-center gap-0.5 xl:gap-1" aria-label="{{ __('nav.main') }}">
        @foreach($navItems as $item)
          @php $navCurrent = \App\Support\WebUrls::isCurrentNav($item['route']); @endphp
          <a href="{{ $item['href'] }}" class="px-3 xl:px-3.5 py-2 rounded-lg text-sm font-semibold tracking-tight transition-colors whitespace-nowrap {{ $navCurrent ? 'lh-nav-link lh-nav-link--current text-white bg-white/20 shadow-sm shadow-black/10' : 'lh-nav-link text-white/85 hover:text-white hover:bg-white/10' }}" @if($navCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
      </nav>
      <div class="relative z-20 flex flex-1 justify-end items-center gap-1.5 sm:gap-2 min-w-0 shrink-0 pointer-events-none">
        @if($langCurrent)
        <div class="lh-lang-switcher relative shrink-0 pointer-events-auto" data-lh-lang-switcher>
          <button type="button" class="inline-flex items-center justify-center gap-1.5 h-10 px-2.5 sm:px-3 rounded-xl border border-white/30 text-white hover:bg-white/10 transition-all" aria-label="{{ __('lang.switch') }}" aria-haspopup="listbox" aria-expanded="false" data-lh-lang-trigger>
            <span class="text-base leading-none select-none" aria-hidden="true">{{ $langCurrent['flag'] }}</span>
          </button>
          <div class="lh-lang-switcher__menu hidden absolute right-0 top-full mt-1.5 min-w-[10.5rem] py-1 rounded-xl bg-white/95 text-ink premium-header-blur shadow-lg shadow-black/15 ring-1 ring-black/8 z-[70]" role="listbox" data-lh-lang-menu>
            @foreach($langItems as $langItem)
              <a href="{{ $langItem['href'] }}" class="flex items-center gap-2.5 px-3 py-2 text-sm transition-colors {{ $langItem['current'] ? 'bg-black/[0.04] font-semibold text-ink' : 'text-ink/75 hover:bg-black/[0.04] hover:text-ink' }}" hreflang="{{ $langItem['locale'] }}" lang="{{ $langItem['locale'] }}">
                <span class="text-base leading-none shrink-0">{{ $langItem['flag'] }}</span>
                <span class="flex-1 min-w-0 truncate">{{ $langItem['name'] }}</span>
              </a>
            @endforeach
          </div>
        </div>
        @endif
        <a href="{{ \App\Support\WebUrls::searchAnchor() }}" class="lh-header-cta hidden sm:inline-flex lg:hidden items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap bg-white/15 text-white hover:bg-white/25 ring-1 ring-white/20 pointer-events-auto">{{ __('nav.book') }}</a>
        <button id="mobile-menu-toggle" type="button" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl transition-all border border-white/30 text-white hover:bg-white/10 pointer-events-auto" aria-label="{{ __('nav.open_menu') }}" aria-expanded="false" aria-controls="mobile-drawer">
          <svg id="mobile-menu-open-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          <svg id="mobile-menu-close-icon" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
    <div id="mobile-drawer-backdrop" class="fixed inset-0 bg-ink/40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden z-50"></div>
    <aside id="mobile-drawer" class="fixed top-0 right-0 h-full w-[86vw] max-w-sm bg-white/95 text-ink premium-header-blur shadow-2xl shadow-black/15 translate-x-full transition-transform duration-300 lg:hidden z-[60] border-l border-black/8">
      <div class="flex items-center justify-between px-5 py-4 border-b border-black/8">
        <a href="{{ \App\Support\WebUrls::home() }}" class="flex items-center gap-2 min-w-0" aria-label="{{ __('nav.home_aria') }}">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-logo text-white ring-1 ring-black/10 shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          </span>
          <span class="flex flex-col leading-tight text-ink min-w-0"><span class="text-xs font-medium tracking-tight">Like</span><span class="text-xs font-bold tracking-wide uppercase">HOME</span></span>
        </a>
        <button id="mobile-drawer-close" type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-black/10 text-ink hover:bg-black/[0.04] transition-all" aria-label="{{ __('nav.close_menu') }}">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <nav class="px-5 py-6 flex flex-col min-h-[calc(100vh-81px)]" aria-label="{{ __('nav.mobile') }}">
        @foreach($navItems as $item)
          @php $navCurrent = \App\Support\WebUrls::isCurrentNav($item['route']); @endphp
          <a href="{{ $item['href'] }}" class="py-3.5 text-[15px] border-b border-black/[0.06] first:pt-0 transition-colors hover:text-ink {{ $navCurrent ? 'text-ink font-semibold' : 'text-ink/70 font-medium' }}" @if($navCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
        <a href="{{ \App\Support\WebUrls::searchAnchor() }}" class="mt-6 py-3.5 text-center rounded-xl bg-cta text-white text-sm font-semibold shadow-md shadow-black/10 hover:brightness-110 transition-all">{{ __('nav.book_search') }}</a>
      </nav>
    </aside>
  </div>
</header>
<main>
  @yield('content')
</main>
<footer class="bg-logo text-white border-t border-white/10 mt-6 md:mt-8">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-16 grid grid-cols-1 md:grid-cols-12 gap-12 md:gap-10 lg:gap-14">
    <div class="md:col-span-5 space-y-5">
      <div class="flex items-start gap-4">
        <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full bg-white/10 ring-2 ring-white/20 shadow-lg shadow-black/25" role="img" aria-label="Like HOME">
          <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </span>
        <p class="text-sm text-white leading-relaxed pt-0.5 max-w-md">{{ __('footer.tagline') }}</p>
      </div>
    </div>
    <div class="md:col-span-4 space-y-4">
      <h4 class="text-white font-semibold text-xs uppercase tracking-[0.22em]">{{ __('nav.footer') }}</h4>
      <nav class="flex flex-col gap-2.5 text-sm" aria-label="{{ __('nav.footer') }}">
        @foreach($navItems as $item)
          @php $fCurrent = \App\Support\WebUrls::isCurrentNav($item['route']); @endphp
          <a href="{{ $item['href'] }}" class="{{ $fCurrent ? 'text-white font-semibold hover:text-zinc-200 transition-colors' : 'text-white hover:text-zinc-200 transition-colors' }}" @if($fCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
        <a href="{{ \App\Support\WebUrls::searchAnchor() }}" class="text-white hover:text-zinc-200 transition-colors pt-1">{{ __('nav.book_footer') }}</a>
      </nav>
    </div>
    <div class="md:col-span-3 space-y-4">
      <h4 class="text-white font-semibold text-xs uppercase tracking-[0.22em]">{{ __('footer.contact') }}</h4>
      <div class="flex flex-col gap-1 text-sm">
        <span class="text-white text-xs uppercase tracking-wide">{{ __('footer.email') }}</span>
        <a href="mailto:{{ $lhFooterEmail }}" class="text-white hover:text-zinc-200 transition-colors font-medium">{{ $lhFooterEmail }}</a>
        <span class="text-white text-xs uppercase tracking-wide mt-4">{{ __('footer.location') }}</span>
        <span class="text-white">{{ $lhFooterCity }}</span>
      </div>
    </div>
  </div>
  <div class="border-t border-white/10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">
      <div class="text-center text-[11px] text-white/80 leading-relaxed max-w-3xl mx-auto">
        <p class="font-semibold text-white">{{ lh_company_legal_name() }}</p>
        <p>{{ __('footer.legal_idno') }}: {{ lh_company_idno() }} · {{ lh_company_legal_address() }}</p>
      </div>
      <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4" aria-label="{{ __('footer.payment_logos') }}">
        <img src="{{ asset('assets/img/payments/maib.png') }}" alt="maib" width="72" height="28" class="h-7 w-auto" loading="lazy" decoding="async">
        <img src="{{ asset('assets/img/payments/visa.png') }}" alt="Visa" width="48" height="16" class="h-4 w-auto" loading="lazy" decoding="async">
        <img src="{{ asset('assets/img/payments/mastercard.png') }}" alt="Mastercard" width="48" height="16" class="h-4 w-auto" loading="lazy" decoding="async">
        <img src="{{ asset('assets/img/payments/amex.png') }}" alt="American Express" width="48" height="16" class="h-4 w-auto" loading="lazy" decoding="async">
      </div>
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-white">
        <div>© {{ date('Y') }} Like HOME. {{ __('footer.rights') }}</div>
        <div class="flex flex-wrap justify-center items-center gap-x-6 gap-y-2 sm:gap-x-8">
          <a href="{{ \App\Support\WebUrls::page('terms') }}" class="text-white hover:text-zinc-200 transition-colors">{{ __('footer.terms') }}</a>
          <a href="{{ \App\Support\WebUrls::page('privacy') }}" class="text-white hover:text-zinc-200 transition-colors">{{ __('footer.privacy') }}</a>
          <button type="button" id="lh-footer-cookie-settings" class="text-white hover:text-zinc-200 transition-colors bg-transparent border-0 cursor-pointer p-0 text-xs font-inherit underline decoration-white/30 underline-offset-2">{{ __('footer.cookies') }}</button>
        </div>
      </div>
    </div>
  </div>
</footer>
@php require base_path('app/Legacy/includes/cookie_banner.php'); @endphp
@php $lhFpLocaleJs = match(app()->getLocale()) { 'en' => 'en', 'ru' => 'ru', default => 'ro' }; @endphp
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if($lhFpLocaleJs !== 'en')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/{{ $lhFpLocaleJs }}.js"></script>
@endif
<script>window.lhFlatpickrLocale = @json($lhFpLocaleJs);</script>
@php \lh_i18n_script_tags(); @endphp
<script src="{{ asset('assets/js/scripts.js') }}"></script>
<script src="{{ asset('assets/js/cookie-consent.js') }}"></script>
<script src="{{ asset('assets/js/property-card-slider.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body, toggleBtn = document.getElementById('mobile-menu-toggle'), closeBtn = document.getElementById('mobile-drawer-close'), drawer = document.getElementById('mobile-drawer'), backdrop = document.getElementById('mobile-drawer-backdrop'), openIcon = document.getElementById('mobile-menu-open-icon'), closeIcon = document.getElementById('mobile-menu-close-icon');
  if (!toggleBtn || !drawer || !backdrop) return;
  function openDrawer() { drawer.classList.remove('translate-x-full'); backdrop.classList.remove('opacity-0', 'pointer-events-none'); backdrop.classList.add('opacity-100'); body.classList.add('mobile-drawer-open'); toggleBtn.setAttribute('aria-expanded', 'true'); openIcon?.classList.add('hidden'); closeIcon?.classList.remove('hidden'); }
  function closeDrawer() { drawer.classList.add('translate-x-full'); backdrop.classList.add('opacity-0', 'pointer-events-none'); backdrop.classList.remove('opacity-100'); body.classList.remove('mobile-drawer-open'); toggleBtn.setAttribute('aria-expanded', 'false'); openIcon?.classList.remove('hidden'); closeIcon?.classList.add('hidden'); }
  toggleBtn.addEventListener('click', function () { drawer.classList.contains('translate-x-full') ? openDrawer() : closeDrawer(); });
  closeBtn?.addEventListener('click', closeDrawer); backdrop.addEventListener('click', closeDrawer);
  drawer.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', closeDrawer); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
  const siteHeader = document.getElementById('site-header');
  if (siteHeader) { function lhHeaderScroll() { siteHeader.classList.toggle('is-scrolled', window.scrollY > 8); } lhHeaderScroll(); window.addEventListener('scroll', lhHeaderScroll, { passive: true }); }
  document.querySelectorAll('[data-lh-lang-trigger]').forEach(function (btn) {
    const menu = btn.parentElement?.querySelector('[data-lh-lang-menu]');
    if (!menu) return;
    btn.addEventListener('click', function () { const open = menu.classList.toggle('hidden'); menu.classList.toggle('lh-lang-switcher__menu--open', !open); btn.setAttribute('aria-expanded', String(!open)); });
  });
});
</script>
@stack('scripts')
</body>
</html>
