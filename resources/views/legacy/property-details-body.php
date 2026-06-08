<div id="lh-pd-main-wrap" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-28 lg:pb-0">

<a href="<?= htmlspecialchars(lh_locale_url(), ENT_QUOTES, 'UTF-8') ?>" id="lh-pd-back-link" class="lg:hidden text-sm text-cta/80 hover:text-ink transition-colors mb-6 inline-block">← <?= htmlspecialchars(__('booking.back'), ENT_QUOTES, 'UTF-8') ?></a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:items-start">

<!-- MAIN COLUMN: header → gallery → features → description → amenities -->
<div id="lh-pd-main-col" class="lg:col-span-2 space-y-10 min-w-0">

<!-- PROPERTY HEADER (title + address above gallery on all breakpoints) -->
<div class="space-y-2 lg:space-y-3">
<h1 class="text-3xl sm:text-4xl font-black tracking-tight text-ink">
<?= htmlspecialchars($lhPropTitleRaw, ENT_QUOTES, 'UTF-8') ?>
</h1>
<?php
$addressDisplay = $locationLine !== '' ? $locationLine : (trim((string)($property['location'] ?? '')) ?: '');
?>
<?php if ($addressDisplay !== ''): ?>
<p class="text-sm lg:text-[0.9375rem] text-blue-grey font-medium leading-relaxed max-w-3xl">
<?= htmlspecialchars($addressDisplay, ENT_QUOTES, 'UTF-8') ?>
</p>
<?php endif; ?>
</div>

<!-- GALLERY -->
<?php if (!empty($images)): ?>
<div class="swiper mainSlider rounded-3xl overflow-hidden shadow-xl shadow-black/10 border border-black/5 bg-zinc-900">
<div class="swiper-wrapper">
<?php foreach ($images as $img): ?>
<div class="swiper-slide">
<div class="relative w-full aspect-[4/3] bg-zinc-900">
<img src="<?= htmlspecialchars(lh_property_image_url($propertyIdForImages, trim($img), 'full'), ENT_QUOTES, 'UTF-8') ?>" class="absolute inset-0 w-full h-full object-cover object-center cursor-zoom-in" alt="" decoding="async">
</div>
</div>
<?php endforeach; ?>
</div>
<div class="swiper-pagination"></div>
</div>
<?php else: ?>
<div class="rounded-3xl border border-black/5 bg-white/80 flex items-center justify-center h-[min(280px,40vh)] text-blue-grey font-semibold shadow-inner">
Fără imagini
</div>
<?php endif; ?>

<?php if (count($images) > 1): ?>
<div id="lh-pd-thumbs" class="mt-3">
<div class="lh-pd-thumbs-scroll">
<div class="lh-pd-thumbs-grid">
<?php foreach ($images as $idx => $img): ?>
<div class="lh-pd-thumbs-cell cursor-pointer rounded-xl overflow-hidden border border-black/5 hover:border-cta/40 transition bg-white/80"
     data-thumb-index="<?= (int)$idx ?>"
     role="button"
     tabindex="0"
     aria-label="<?= htmlspecialchars(__('booking.image_n', ['n' => (string) ((int) $idx + 1)]), ENT_QUOTES, 'UTF-8') ?>">
<img src="<?= htmlspecialchars(lh_property_image_url($propertyIdForImages, trim($img), 'thumb'), ENT_QUOTES, 'UTF-8') ?>"
     class="block h-full w-full object-cover" alt="" loading="lazy">
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<!-- KEY FEATURES -->
<div class="border-y border-black/6 py-5">
<div class="grid grid-cols-3 gap-3 sm:gap-6">
<div class="flex items-center gap-2 sm:gap-3 min-w-0">
<div class="w-9 h-9 sm:w-10 sm:h-10 shrink-0 bg-brand-100 rounded-xl flex items-center justify-center text-cta/70 border border-black/8">
<i data-lucide="users" class="w-4 h-4 sm:w-5 sm:h-5"></i>
</div>
<div class="min-w-0">
<p class="text-xs text-blue-grey leading-tight"><?= htmlspecialchars(__('booking.capacity'), ENT_QUOTES, 'UTF-8') ?></p>
<p class="font-bold text-sm leading-tight"><?= (int)$property['sleep_capacity'] ?> <?= htmlspecialchars(__('booking.persons_abbr'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
</div>
<div class="flex items-center gap-2 sm:gap-3 min-w-0">
<div class="w-9 h-9 sm:w-10 sm:h-10 shrink-0 bg-brand-100 rounded-xl flex items-center justify-center text-cta/70 border border-black/8">
<i data-lucide="bed-double" class="w-4 h-4 sm:w-5 sm:h-5"></i>
</div>
<div class="min-w-0">
<p class="text-xs text-blue-grey leading-tight"><?= htmlspecialchars(__('booking.rooms'), ENT_QUOTES, 'UTF-8') ?></p>
<p class="font-bold text-sm leading-tight"><?= (int)$property['rooms'] ?></p>
</div>
</div>
<div class="flex items-center gap-2 sm:gap-3 min-w-0">
<div class="w-9 h-9 sm:w-10 sm:h-10 shrink-0 bg-brand-100 rounded-xl flex items-center justify-center text-cta/70 border border-black/8">
<i data-lucide="maximize" class="w-4 h-4 sm:w-5 sm:h-5"></i>
</div>
<div class="min-w-0">
<p class="text-xs text-blue-grey leading-tight"><?= htmlspecialchars(__('booking.area'), ENT_QUOTES, 'UTF-8') ?></p>
<p class="font-bold text-sm leading-tight"><?= (int)$property['area_sqm'] ?> m²</p>
</div>
</div>
</div>
</div>

<!-- DESCRIPTION -->
<div>
<h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars(__('booking.about_property'), ENT_QUOTES, 'UTF-8') ?></h2>
<p class="text-ink/80 leading-relaxed whitespace-pre-line">
<?= htmlspecialchars($property['description_long'] ?? $property['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</p>
</div>

<!-- AMENITIES (icons + labels match admin Facilități & Dotări catalog) -->
<?php if (!empty($amenities)): ?>
<div>
<h2 class="text-2xl font-bold mb-6"><?= htmlspecialchars(__('booking.amenities'), ENT_QUOTES, 'UTF-8') ?></h2>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4">
<?php foreach ($amenities as $a):
    if (!is_string($a) && !is_int($a)) {
        continue;
    }
    [$amenityLabel, $amenityIcon] = lh_property_amenity_resolve((string) $a);
    if ($amenityLabel === '') {
        continue;
    }
    ?>
<div class="flex items-center gap-3 bg-white/75 border border-black/5 p-4 rounded-2xl hover:bg-brand-50 transition backdrop-blur-sm">
<div class="w-8 h-8 bg-brand-100 rounded-lg flex items-center justify-center text-cta/70 border border-black/8 shrink-0">
<i data-lucide="<?= htmlspecialchars($amenityIcon, ENT_QUOTES, 'UTF-8') ?>" class="w-4 h-4"></i>
</div>
<span class="text-sm font-medium text-ink/85">
<?= htmlspecialchars($amenityLabel, ENT_QUOTES, 'UTF-8') ?>
</span>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

</div>

<!-- DESKTOP: sticky booking column (ascuns la lg + viewport scurt; vezi LH_PD_BOOKING_MIN_VIEWPORT_HEIGHT_PX) -->
<div id="lh-booking-desktop-col" class="hidden lg:block lg:sticky lg:top-24 lg:self-start min-w-0">
<div id="lh-booking-desktop-slot">
<?php require base_path('app/Legacy/includes/property_booking_widget.php'); ?>
</div>
</div>

</div>

<!-- MAP (full content width below grid) -->
<section class="mt-10 md:mt-14 min-w-0 max-w-full" aria-labelledby="lh-map-heading">
<h2 id="lh-map-heading" class="text-2xl font-bold mb-4 flex items-center gap-2">
<span class="w-10 h-10 bg-brand-100 rounded-xl flex items-center justify-center text-cta/70 border border-black/8 shrink-0" aria-hidden="true">
<i data-lucide="map-pin" class="w-5 h-5"></i>
</span>
<?= htmlspecialchars(__('booking.location'), ENT_QUOTES, 'UTF-8') ?>
</h2>
<?php if ($mapIframeSrc !== ''): ?>
<div class="relative w-full max-w-full overflow-hidden rounded-2xl border border-black/10 bg-surface h-[220px] sm:h-[280px] md:h-[360px] lg:h-[400px]">
<iframe
  title="<?= htmlspecialchars(__('booking.map_title', ['property' => $lhPropTitleRaw]), ENT_QUOTES, 'UTF-8') ?>"
  class="absolute inset-0 block h-full w-full border-0"
  src="<?= htmlspecialchars($mapIframeSrc, ENT_QUOTES, 'UTF-8') ?>"
  loading="lazy"
  referrerpolicy="strict-origin-when-cross-origin"
  allowfullscreen></iframe>
</div>
<?php else: ?>
<p class="text-blue-grey text-sm py-8 px-4 rounded-2xl border border-black/8 bg-white/60 text-center">
<?= htmlspecialchars(__('booking.map_unavailable'), ENT_QUOTES, 'UTF-8') ?>
</p>
<?php endif; ?>
</section>

<?php if (!empty($same_area_properties)): ?>
<section class="mt-10 md:mt-14 min-w-0 max-w-full" aria-labelledby="lh-same-area-heading">
<h2 id="lh-same-area-heading" class="text-2xl font-bold mb-2"><?= htmlspecialchars(__('booking.same_area'), ENT_QUOTES, 'UTF-8') ?></h2>
<?php if ($same_area_label !== ''): ?>
<p class="text-sm text-blue-grey font-medium mb-6"><?= htmlspecialchars($same_area_label, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
<?php foreach ($same_area_properties as $neighbor): ?>
<?= view('components.property-card', ['property' => $neighbor, 'checkIn' => $check_in, 'checkOut' => $check_out, 'guests' => $guests])->render() ?>
<?php endforeach; ?>
</div>
<div class="flex justify-center sm:justify-start">
<a href="<?= htmlspecialchars($same_area_see_more_url, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center justify-center gap-2 bg-white border-2 border-cta text-cta hover:bg-brand-50 font-bold px-8 py-3.5 rounded-2xl transition-colors shadow-sm">
<?= htmlspecialchars(__('booking.see_more'), ENT_QUOTES, 'UTF-8') ?>
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
</a>
</div>
</section>
<?php endif; ?>

</div>

<!-- MOBILE (și desktop viewport scurt): bară fixă jos -->
<div id="lh-booking-mobile-bar" class="lg:hidden fixed bottom-0 inset-x-0 z-[90] border-t border-black/8 bg-white/95 premium-header-blur px-4 sm:px-6 lg:px-8 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_30px_rgb(0_0_0/0.08)]">
<div class="max-w-6xl mx-auto flex items-center justify-between gap-2">
<div class="min-w-0 flex-1">
<p class="flex flex-nowrap items-baseline gap-x-0.5 min-w-0 whitespace-nowrap text-lg font-black text-ink tabular-nums leading-none"><span class="text-xs sm:text-sm font-bold text-blue-grey shrink-0"><?= htmlspecialchars(__('booking.mobile_from'), ENT_QUOTES, 'UTF-8') ?> </span><?= htmlspecialchars($priceFormatted, ENT_QUOTES, 'UTF-8') ?> <span class="text-xs sm:text-sm font-bold text-blue-grey shrink-0"><?= htmlspecialchars(__('booking.per_night'), ENT_QUOTES, 'UTF-8') ?></span></p>
</div>
<button type="button" id="lh-open-booking-sheet" class="shrink-0 inline-flex items-center justify-center gap-1.5 bg-cta hover:brightness-110 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-black/10 transition-all">
<?= htmlspecialchars(__('booking.book_now'), ENT_QUOTES, 'UTF-8') ?>
</button>
</div>
</div>

<!-- MOBILE: bottom sheet -->
<div id="lh-booking-overlay" class="fixed inset-0 z-[100] bg-black/40 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>
<div
  id="lh-booking-sheet"
  class="fixed inset-x-0 bottom-0 z-[101] max-h-[min(88dvh,840px)] rounded-t-3xl border border-black/10 bg-white shadow-2xl translate-y-full transition-transform duration-300 ease-out lg:hidden flex flex-col"
  role="dialog"
  aria-modal="true"
  aria-labelledby="lh-booking-sheet-title"
  aria-hidden="true">
<div class="shrink-0 flex items-center justify-between gap-3 px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-2 border-b border-black/6">
<h2 id="lh-booking-sheet-title" class="text-lg font-black text-ink tracking-tight"><?= htmlspecialchars(__('booking.booking_title'), ENT_QUOTES, 'UTF-8') ?></h2>
<button type="button" id="lh-close-booking-sheet" class="p-2 rounded-xl text-blue-grey hover:bg-brand-100 hover:text-ink transition-colors" aria-label="<?= htmlspecialchars(__('booking.close'), ENT_QUOTES, 'UTF-8') ?>">
<i data-lucide="x" class="w-6 h-6"></i>
</button>
</div>
<div id="lh-booking-sheet-body" class="flex-1 overflow-y-auto overscroll-contain px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]"></div>
</div>

<div id="lh-booking-toast" class="lh-toast" role="status" aria-live="polite"></div>

<div id="lh-booking-confirm-root" class="lh-booking-confirm-root" hidden aria-hidden="true">
<div class="lh-booking-confirm-overlay" id="lh-booking-confirm-overlay"></div>
<div
  class="lh-booking-confirm-panel p-6 sm:p-8"
  role="dialog"
  aria-modal="true"
  aria-labelledby="lh-booking-confirm-title">
<h2 id="lh-booking-confirm-title" class="text-lg font-black text-ink tracking-tight"><?= htmlspecialchars(__('booking.confirm_title'), ENT_QUOTES, 'UTF-8') ?></h2>
<p class="text-sm text-blue-grey font-medium mt-1 mb-4"><?= htmlspecialchars(__('booking.confirm_subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
<dl class="space-y-2 text-sm mb-6">
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_property'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-property" class="font-bold text-ink"></dd></div>
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_period'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-period" class="font-medium text-ink"></dd></div>
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_guests'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-guests" class="font-medium text-ink"></dd></div>
<div id="lh-confirm-price-break" class="hidden border-b border-black/6 pb-2 text-sm space-y-1.5">
<p class="text-xs font-semibold text-blue-grey uppercase tracking-wide m-0"><?= htmlspecialchars(__('booking.subtotal'), ENT_QUOTES, 'UTF-8') ?></p>
<div id="lh-confirm-base-line" class="font-medium text-ink tabular-nums leading-snug"></div>
<div id="lh-confirm-discount-line" class="hidden font-medium text-emerald-800 tabular-nums leading-snug"></div>
<div id="lh-confirm-coupon-line" class="hidden font-medium text-emerald-800 tabular-nums leading-snug"></div>
<div id="lh-confirm-extra-line" class="hidden font-medium text-ink tabular-nums leading-snug"></div>
<p id="lh-confirm-extra-note" class="hidden text-[10px] text-blue-grey font-medium leading-snug m-0"></p>
</div>
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.payment_method_label'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-payment-method" class="font-medium text-ink"></dd></div>
<div class="lh-confirm-pricing-row border-b border-black/6 pb-2">
<dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide lh-confirm-pricing-label"><?= htmlspecialchars(__('booking.confirm_total_label'), ENT_QUOTES, 'UTF-8') ?></dt>
<dd id="lh-confirm-total" class="font-bold text-cta m-0 tabular-nums"></dd>
</div>
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_name'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-name" class="font-medium text-ink break-words"></dd></div>
<div class="flex flex-col gap-0.5 border-b border-black/6 pb-2"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_phone'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-phone" class="font-medium text-ink"></dd></div>
<div class="flex flex-col gap-0.5 pb-1"><dt class="text-blue-grey font-semibold text-xs uppercase tracking-wide"><?= htmlspecialchars(__('booking.confirm_email'), ENT_QUOTES, 'UTF-8') ?></dt><dd id="lh-confirm-email" class="font-medium text-ink break-all"></dd></div>
</dl>
<div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
<button type="button" id="lh-booking-confirm-back" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3.5 rounded-xl font-bold border-2 border-black/10 text-ink hover:bg-brand-50 transition-colors"><?= htmlspecialchars(__('booking.confirm_back'), ENT_QUOTES, 'UTF-8') ?></button>
<button type="button" id="lh-booking-confirm-submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-cta hover:brightness-110 text-white px-5 py-3.5 rounded-xl font-bold transition-all"><?= htmlspecialchars(__('booking.confirm_submit'), ENT_QUOTES, 'UTF-8') ?></button>
</div>
</div>
</div>

<div id="lh-booking-success-banner" class="lh-booking-success-banner" hidden role="status" aria-live="polite">
<div class="lh-booking-success-banner__inner">
<div class="min-w-0 flex-1">
<strong><?= htmlspecialchars(__('booking.success_title'), ENT_QUOTES, 'UTF-8') ?></strong>
<p id="lh-booking-success-text" class="text-blue-grey font-medium"></p>
</div>
<button type="button" class="lh-booking-success-banner__close" id="lh-booking-success-close"><?= htmlspecialchars(__('booking.success_close'), ENT_QUOTES, 'UTF-8') ?></button>
</div>
</div>

<?php if (!empty($images)): ?>
<div
  id="lh-gallery-lightbox"
  class="fixed inset-0 z-[120] flex flex-col bg-transparent"
  hidden
  role="dialog"
  aria-modal="true"
  aria-label="<?= htmlspecialchars(__('booking.gallery_aria'), ENT_QUOTES, 'UTF-8') ?>"
  aria-hidden="true">
  <div class="absolute inset-0 z-0 cursor-pointer lh-gallery-lightbox-backdrop" id="lh-gallery-lightbox-backdrop" aria-hidden="true"></div>
  <button
    type="button"
    id="lh-gallery-lightbox-close"
    class="absolute top-3 right-3 z-20 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 border border-white/20 transition-colors pointer-events-auto"
    aria-label="<?= htmlspecialchars(__('booking.gallery_close'), ENT_QUOTES, 'UTF-8') ?>">
    <i data-lucide="x" class="w-6 h-6" aria-hidden="true"></i>
  </button>
  <div class="lh-gallery-lightbox-inner relative z-10 flex flex-1 min-h-0 w-full flex-col pt-14 pb-2 px-2 sm:px-4 pointer-events-none">
    <div class="swiper lh-gallery-lightbox-swiper flex-1 min-h-0 w-full pointer-events-auto">
      <div class="swiper-wrapper">
        <?php foreach ($images as $img): ?>
        <div class="swiper-slide">
          <div class="swiper-zoom-container">
            <img src="<?= htmlspecialchars(lh_property_image_url($propertyIdForImages, trim($img), 'full'), ENT_QUOTES, 'UTF-8') ?>" alt="" decoding="async">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="lh-lb-zoom-tools flex shrink-0 items-center justify-center gap-2 pt-2" role="toolbar" aria-label="<?= htmlspecialchars(__('booking.zoom_toolbar'), ENT_QUOTES, 'UTF-8') ?>">
        <button
          type="button"
          id="lh-lb-zoom-out"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 border border-white/20 transition-colors"
          aria-label="<?= htmlspecialchars(__('booking.zoom_out'), ENT_QUOTES, 'UTF-8') ?>">
          <i data-lucide="zoom-out" class="w-5 h-5" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          id="lh-lb-zoom-in"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 border border-white/20 transition-colors"
          aria-label="<?= htmlspecialchars(__('booking.zoom_in'), ENT_QUOTES, 'UTF-8') ?>">
          <i data-lucide="zoom-in" class="w-5 h-5" aria-hidden="true"></i>
        </button>
      </div>
      <div class="swiper-pagination shrink-0 pt-2" id="lh-gallery-lightbox-pagination"></div>
    </div>
  </div>
</div>
<?php endif; ?>
