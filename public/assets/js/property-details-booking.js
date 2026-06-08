
var mainSwiper = null;
var lightboxSwiper = null;
var lhGalleryLightboxRoot = document.getElementById('lh-gallery-lightbox');
var lhGalleryLightboxLastFocus = null;
var lhGalleryLightboxBodyOverflow = '';

function lhCloseGalleryLightbox() {
  if (!lhGalleryLightboxRoot || lhGalleryLightboxRoot.hasAttribute('hidden')) return;
  lhLbZoomReset();
  if (mainSwiper && lightboxSwiper) {
    mainSwiper.slideTo(lightboxSwiper.activeIndex, 0);
  }
  lhGalleryLightboxRoot.setAttribute('hidden', '');
  lhGalleryLightboxRoot.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = lhGalleryLightboxBodyOverflow;
  var prev = lhGalleryLightboxLastFocus;
  lhGalleryLightboxLastFocus = null;
  if (prev && typeof prev.focus === 'function') {
    try {
      prev.focus();
    } catch (err) {}
  }
}

function lhLbZoomReset() {
  if (lightboxSwiper && lightboxSwiper.zoom && typeof lightboxSwiper.zoom.out === 'function') {
    lightboxSwiper.zoom.out();
  }
}

function lhOpenGalleryLightbox(index) {
  if (!lhGalleryLightboxRoot || !lightboxSwiper) return;
  lhGalleryLightboxLastFocus = document.activeElement;
  lhGalleryLightboxRoot.removeAttribute('hidden');
  lhGalleryLightboxRoot.setAttribute('aria-hidden', 'false');
  lhGalleryLightboxBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
  var i = typeof index === 'number' && index >= 0 ? index : 0;
  lightboxSwiper.slideTo(i, 0);
  lhLbZoomReset();
  lightboxSwiper.update();
  requestAnimationFrame(function () {
    lightboxSwiper.update();
  });
  var closeBtn = document.getElementById('lh-gallery-lightbox-close');
  if (closeBtn) {
    requestAnimationFrame(function () {
      closeBtn.focus();
    });
  }
  lhRefreshLucide();
}

var lbSwiperEl = document.querySelector('.lh-gallery-lightbox-swiper');
if (lbSwiperEl && lbSwiperEl.querySelector('.swiper-slide')) {
  lightboxSwiper = new Swiper('.lh-gallery-lightbox-swiper', {
    slidesPerView: 1,
    spaceBetween: 12,
    keyboard: { enabled: true },
    zoom: {
      maxRatio: 3,
      minRatio: 1,
      toggle: true
    },
    pagination: {
      el: '#lh-gallery-lightbox-pagination',
      type: 'fraction'
    },
    on: {
      slideChange: function () {
        lhLbZoomReset();
      }
    }
  });

  var zIn = document.getElementById('lh-lb-zoom-in');
  var zOut = document.getElementById('lh-lb-zoom-out');
  if (zIn) {
    zIn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (lightboxSwiper && lightboxSwiper.zoom && typeof lightboxSwiper.zoom.in === 'function') {
        lightboxSwiper.zoom.in();
      }
    });
  }
  if (zOut) {
    zOut.addEventListener('click', function (e) {
      e.stopPropagation();
      lhLbZoomReset();
    });
  }
}

function lhPdSyncThumbStrip(activeIdx) {
  var root = document.getElementById('lh-pd-thumbs');
  if (!root) return;
  var cells = root.querySelectorAll('[data-thumb-index]');
  var i;
  for (i = 0; i < cells.length; i++) {
    var el = cells[i];
    var idx = parseInt(el.getAttribute('data-thumb-index'), 10);
    if (idx === activeIdx) {
      el.classList.add('lh-pd-thumbs-cell--active');
      try {
        el.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
      } catch (err) {
        el.scrollIntoView(false);
      }
    } else {
      el.classList.remove('lh-pd-thumbs-cell--active');
    }
  }
}

var mainSliderEl = document.querySelector('.mainSlider');
if (mainSliderEl && mainSliderEl.querySelector('.swiper-slide')) {
  mainSwiper = new Swiper(mainSliderEl, {
    pagination: { el: mainSliderEl.querySelector('.swiper-pagination') },
    grabCursor: true,
    preventClicks: false,
    on: {
      slideChange: function (swiper) {
        lhPdSyncThumbStrip(swiper.activeIndex);
      },
      click: function (swiper, event) {
        if (!event || !event.target) return;
        if (event.target.closest && event.target.closest('.swiper-pagination')) return;
        var slide = event.target.closest ? event.target.closest('.swiper-slide') : null;
        if (!slide || !swiper.el.contains(slide)) return;
        var slides = Array.prototype.slice.call(swiper.slides);
        var idx = slides.indexOf(slide);
        if (idx < 0) idx = swiper.activeIndex;
        lhOpenGalleryLightbox(idx);
      }
    }
  });
  lhPdSyncThumbStrip(mainSwiper.activeIndex);
}

if (lhGalleryLightboxRoot) {
  var lhLbClose = document.getElementById('lh-gallery-lightbox-close');
  var lhLbBackdrop = document.getElementById('lh-gallery-lightbox-backdrop');
  if (lhLbClose) {
    lhLbClose.addEventListener('click', function (e) {
      e.stopPropagation();
      lhCloseGalleryLightbox();
    });
  }
  lhGalleryLightboxRoot.addEventListener('click', function (e) {
    if (lhGalleryLightboxRoot.hasAttribute('hidden')) return;
    if (e.target.closest && e.target.closest('#lh-gallery-lightbox-close')) return;
    if (e.target.closest && e.target.closest('.swiper-pagination')) return;
    if (e.target.closest && e.target.closest('img')) return;
    lhCloseGalleryLightbox();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!lhGalleryLightboxRoot || lhGalleryLightboxRoot.hasAttribute('hidden')) return;
    e.preventDefault();
    lhCloseGalleryLightbox();
  });
}

document.querySelectorAll('[data-thumb-index]').forEach(function (el) {
  var idx = parseInt(el.getAttribute('data-thumb-index'), 10);
  var go = function () {
    if (mainSwiper) mainSwiper.slideTo(idx);
  };
  el.addEventListener('click', go);
  el.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      go();
    }
  });
});

function lhRefreshLucide() {
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
lhRefreshLucide();

function lhFocusNoScroll(el) {
  if (!el || typeof el.focus !== 'function') return;
  try {
    el.focus({ preventScroll: true });
  } catch (err) {
    try {
      el.focus();
    } catch (e2) {}
  }
}

window.LH_CURRENCY = <?= json_encode(lh_currency_client_config(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
function lhCurrencySuffix() {
  var c = window.LH_CURRENCY;
  if (c && c.suffix != null && String(c.suffix) !== '') {
    return String(c.suffix);
  }
  return ' MDL';
}

/** Aligned with Flatpickr altInput (localized month names via lhBookingFpLocale). */
var lhBookingAltFormat = 'd M Y';

function lhFormatCouponLine(code, discountEuro) {
  var amt = discountEuro.toFixed(0) + lhCurrencySuffix();
  if (typeof lhT === 'function') {
    return lhT('booking.coupon_line', { code: String(code).toUpperCase(), amount: amt });
  }
  return '\u00ab' + String(code).toUpperCase() + '\u00bb: -' + amt;
}

var lhPricing = {
  priceStandard: <?= json_encode((float) ($property['price'] ?? 0)) ?>,
  priceWeekend: <?= json_encode(isset($property['price_weekend']) ? (float) $property['price_weekend'] : 0.0) ?>,
  guestsIncluded: <?= json_encode(isset($property['guests_included']) ? (int) $property['guests_included'] : 0) ?>,
  extraGuestPrice: <?= json_encode(isset($property['extra_guest_price']) ? (float) $property['extra_guest_price'] : 0.0) ?>,
  extraGuestUnit: <?= json_encode((string) ($property['extra_guest_unit'] ?? 'per_guest_per_night'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
  stayDiscountsGlobal: <?= json_encode($property['_stay_discounts_global'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
  periods: <?= json_encode(
      array_map(static function (array $r): array {
          $ms = $r['min_stay'] ?? null;

          return [
              'start' => (string) ($r['date_start'] ?? ''),
              'end' => (string) ($r['date_end'] ?? ''),
              'price' => (float) ($r['price'] ?? 0),
              'priceWeekend' => (isset($r['price_weekend']) && $r['price_weekend'] !== null && (float) $r['price_weekend'] > 0)
                  ? (float) $r['price_weekend']
                  : 0,
              'minStay' => ($ms !== null && $ms !== '' && (int) $ms >= 1) ? (int) $ms : null,
              'stayDiscounts' => isset($r['stay_discounts']) && is_array($r['stay_discounts']) ? $r['stay_discounts'] : [],
          ];
      }, $property['_pricing_periods'] ?? []),
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ) ?>
};

var lhMinStayBase = <?= max(1, (int) ($property['min_stay'] ?? 1)) ?>;

function lhEffectiveMinStay(checkInYmd, checkOutYmd) {
  var base = lhMinStayBase;
  if (!checkInYmd || !checkOutYmd || checkOutYmd <= checkInYmd) {
    return base;
  }
  var periods = lhPricing.periods || [];
  var i;
  for (i = 0; i < periods.length; i++) {
    var pr = periods[i];
    if (lhBookingStayFullyInPeriod(checkInYmd, checkOutYmd, pr)) {
      var ms = pr.minStay;
      if (ms != null && ms >= 1) {
        return ms;
      }
      return base;
    }
  }
  return base;
}

function lhNightsLabel(n) {
  n = parseInt(String(n), 10) || 0;
  if (n === 1) return typeof lhT === 'function' ? lhT('booking.night_one') : '1';
  if (n < 1) return typeof lhT === 'function' ? lhT('booking.nights_zero') : '0';
  return typeof lhT === 'function' ? lhT('booking.nights_count', { n: String(n) }) : String(n);
}

function lhMinStayTooShortMsg(eff) {
  var m = parseInt(String(eff), 10);
  if (!m || m < 1) {
    m = lhMinStayBase;
  }
  if (m === 1) {
    return typeof lhT === 'function' ? lhT('booking.checkout_after_checkin') : '';
  }
  return typeof lhT === 'function' ? lhT('booking.min_stay_property', { n: String(m) }) : String(m);
}

function lhIsWeekendNightStartYmd(ymd) {
  var p = String(ymd).split('-');
  if (p.length !== 3) return false;
  var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
  var w = d.getDay();
  return w === 0 || w === 6;
}

function lhYmdInPricingPeriod(ymd, period) {
  return period.start && period.end && period.start <= ymd && ymd < period.end;
}

function lhNightRateEuroForYmd(ymd) {
  var cfg = lhPricing;
  var periods = cfg.periods || [];
  for (var i = 0; i < periods.length; i++) {
    var pr = periods[i];
    if (lhYmdInPricingPeriod(ymd, pr)) {
      var ps = pr.price;
      var pw = pr.priceWeekend > 0 ? pr.priceWeekend : ps;
      return lhIsWeekendNightStartYmd(ymd) ? pw : ps;
    }
  }
  var std = cfg.priceStandard;
  var wnd = cfg.priceWeekend > 0 ? cfg.priceWeekend : std;
  return lhIsWeekendNightStartYmd(ymd) ? wnd : std;
}

function lhYmdAddOne(ymd) {
  var p = String(ymd).split('-');
  var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
  d.setDate(d.getDate() + 1);
  var mo = String(d.getMonth() + 1).padStart(2, '0');
  var day = String(d.getDate()).padStart(2, '0');
  return d.getFullYear() + '-' + mo + '-' + day;
}

function lhBookingStayFullyInPeriod(checkInYmd, checkOutYmd, period) {
  return period.start && period.end && checkInYmd >= period.start && checkOutYmd <= period.end;
}

function lhSelectStayDiscountRules(checkInYmd, checkOutYmd, periods, globalRules) {
  var i;
  for (i = 0; i < (periods || []).length; i++) {
    if (lhBookingStayFullyInPeriod(checkInYmd, checkOutYmd, periods[i])) {
      return periods[i].stayDiscounts || [];
    }
  }
  return globalRules || [];
}

function lhBookingStayDiscountResult(nights, subtotal, rules) {
  var out = { discount: 0, rule: null };
  if (nights < 1 || !rules || rules.length === 0 || subtotal <= 0) return out;
  var bestMn = null;
  var best = null;
  var i;
  for (i = 0; i < rules.length; i++) {
    var r = rules[i];
    var mn = parseInt(String(r.min_nights), 10) || 0;
    if (nights <= mn) continue;
    if (bestMn === null || mn > bestMn) {
      bestMn = mn;
      best = r;
    }
  }
  if (!best) return out;
  var val = parseFloat(String(best.value).replace(',', '.'));
  if (!val || val <= 0) return out;
  var unit = best.unit === 'fixed_stay' ? 'fixed_stay' : 'percent';
  if (unit === 'fixed_stay') {
    out.discount = Math.min(subtotal, val);
  } else {
    out.discount = Math.min(subtotal, subtotal * (val / 100));
  }
  out.rule = best;
  return out;
}

function lhFormatBaseStayLine(nights, baseEuro, nightlyUniform, uniformRate) {
  if (nights < 1) return '';
  var btxt = baseEuro.toFixed(0) + lhCurrencySuffix();
  var nword = nights === 1 ? lhT('booking.night_word') : lhT('booking.nights_word');
  if (nightlyUniform && uniformRate != null) {
    return lhT('booking.base_stay_uniform', {
      n: nights,
      nword: nword,
      rate: uniformRate.toFixed(0) + lhCurrencySuffix(),
      total: btxt,
    });
  }
  var avg = baseEuro / nights;
  return lhT('booking.base_stay_avg', {
    n: nights,
    avg: avg.toFixed(0) + lhCurrencySuffix(),
    total: btxt,
  });
}

function lhFormatDiscountDisplayLine(discountEuro, rule) {
  if (!rule || discountEuro <= 0.005) return '';
  var val = parseFloat(String(rule.value).replace(',', '.'));
  if (!val || val <= 0) return '';
  var unit = rule.unit === 'fixed_stay' ? 'fixed_stay' : 'percent';
  var mn = parseInt(String(rule.min_nights), 10) || 0;
  var dtxt = '−' + discountEuro.toFixed(0) + lhCurrencySuffix();
  var overPhrase =
    mn < 1 ? '' : lhT('booking.over_nights', { n: mn, word: mn === 1 ? lhT('booking.night_word') : lhT('booking.nights_word') });
  if (unit === 'percent') {
    var rounded = Math.round(val);
    var ptxt = Math.abs(val - rounded) < 1e-6 ? String(rounded) : String(val);
    return lhT('booking.discount_percent', { pct: ptxt, over: overPhrase, amount: dtxt });
  }
  return lhT('booking.discount_fixed', { over: overPhrase, amount: dtxt });
}

function lhFormatExtraGuestMathLine(overGuests, pricePerGuest, nights, extraEuro) {
  if (overGuests < 1 || pricePerGuest <= 0 || nights < 1 || extraEuro <= 0.005) return '';
  var gword = overGuests === 1 ? lhT('booking.guest_singular') : lhT('booking.guests_plural');
  var nword = nights === 1 ? lhT('booking.night_word') : lhT('booking.nights_word');
  return lhT('booking.extra_guest_math', {
    over: overGuests,
    guests: gword,
    price: pricePerGuest.toFixed(0) + lhCurrencySuffix(),
    n: nights,
    nword: nword,
    total: extraEuro.toFixed(0) + lhCurrencySuffix(),
  });
}

function lhBookingLengthDiscountEuro(nights, subtotal, rules) {
  return lhBookingStayDiscountResult(nights, subtotal, rules).discount;
}

function lhExtraGuestNoticeText(guestsInt) {
  var cfg = lhPricing;
  var g = parseInt(String(guestsInt), 10) || 1;
  if (
    cfg.guestsIncluded <= 0 ||
    cfg.extraGuestPrice <= 0 ||
    cfg.extraGuestUnit !== 'per_guest_per_night' ||
    g <= cfg.guestsIncluded
  ) {
    return '';
  }
  var over = g - cfg.guestsIncluded;
  var gword = over === 1 ? lhT('booking.guest_singular') : lhT('booking.guests_plural');
  return lhT('booking.extra_guest_notice', {
    price: cfg.extraGuestPrice.toFixed(0) + lhCurrencySuffix(),
    over: over,
    guests: gword,
    included: cfg.guestsIncluded,
  });
}

function lhBookingStayPricingEuro(checkInYmd, checkOutYmd, guestsInt) {
  var empty = {
    nights: 0,
    baseEuro: 0,
    extraGuestEuro: 0,
    subtotal: 0,
    discount: 0,
    total: 0,
    baseLine: '',
    discountLine: '',
    extraGuestMathLine: '',
    extraGuestNote: '',
  };
  var cfg = lhPricing;
  if (!checkInYmd || !checkOutYmd || checkOutYmd <= checkInYmd) return empty;
  var base = 0;
  var nights = 0;
  var cur = checkInYmd;
  var nightlyUniform = true;
  var firstRate = null;
  while (cur < checkOutYmd) {
    var nightRate = lhNightRateEuroForYmd(cur);
    if (firstRate === null) firstRate = nightRate;
    else if (Math.abs(nightRate - firstRate) > 1e-6) nightlyUniform = false;
    base += nightRate;
    cur = lhYmdAddOne(cur);
    nights++;
  }
  var extra = 0;
  var overGuests = 0;
  if (
    cfg.guestsIncluded > 0 &&
    guestsInt > cfg.guestsIncluded &&
    cfg.extraGuestPrice > 0 &&
    cfg.extraGuestUnit === 'per_guest_per_night'
  ) {
    overGuests = guestsInt - cfg.guestsIncluded;
    extra = overGuests * cfg.extraGuestPrice * nights;
  }
  var subtotal = base + extra;
  var rules = lhSelectStayDiscountRules(checkInYmd, checkOutYmd, cfg.periods || [], cfg.stayDiscountsGlobal || []);
  var dr = lhBookingStayDiscountResult(nights, subtotal, rules);
  var disc = dr.discount;
  var total = Math.max(0, subtotal - disc);
  var baseLine = lhFormatBaseStayLine(nights, base, nightlyUniform, firstRate);
  var discountLine = disc > 0.005 ? lhFormatDiscountDisplayLine(disc, dr.rule) : '';
  var extraMath =
    extra > 0.005
      ? lhFormatExtraGuestMathLine(overGuests, cfg.extraGuestPrice, nights, extra)
      : '';
  return {
    nights: nights,
    baseEuro: base,
    extraGuestEuro: extra,
    subtotal: subtotal,
    discount: disc,
    total: total,
    baseLine: baseLine,
    discountLine: discountLine,
    extraGuestMathLine: extraMath,
    extraGuestNote: lhExtraGuestNoticeText(guestsInt),
  };
}

function lhBookingStayTotalEuro(checkInYmd, checkOutYmd, guestsInt) {
  return lhBookingStayPricingEuro(checkInYmd, checkOutYmd, guestsInt).total;
}

function lhGetGuestsIntForPricing() {
  var sel = document.getElementById('guests');
  var v = sel ? parseInt(String(sel.value), 10) : 1;
  if (!v || v < 1) v = 1;
  return v;
}

var lhLastPricePreview = null;
var lhPricePreviewTimer = null;
var lhPricePreviewAbort = null;

function lhGetCouponRawInput() {
  var el = document.getElementById('booking-coupon-code');
  return el ? String(el.value || '').trim() : '';
}

function lhPricePreviewSyncKey(cinYmd, coutYmd, guestsInt, couponRaw) {
  return cinYmd + '|' + coutYmd + '|' + guestsInt + '|' + couponRaw.toUpperCase();
}

function lhScheduleBookingPricePreview(cinYmd, coutYmd, guestsInt, couponRaw) {
  window.clearTimeout(lhPricePreviewTimer);
  lhPricePreviewTimer = window.setTimeout(function () {
    lhRunBookingPricePreview(cinYmd, coutYmd, guestsInt, couponRaw);
  }, 400);
}

function lhRunBookingPricePreview(cinYmd, coutYmd, guestsInt, couponRaw) {
  if (!ajaxBookingPricePreview || !bookingCsrf) return;
  if (lhPricePreviewAbort) {
    lhPricePreviewAbort.abort();
  }
  lhPricePreviewAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;
  var reqKey = lhPricePreviewSyncKey(cinYmd, coutYmd, guestsInt, couponRaw);
  var body = new URLSearchParams({
    csrf_token: bookingCsrf,
    property_id: String(propertyId),
    check_in: cinYmd,
    check_out: coutYmd,
    guests: String(guestsInt),
    coupon_code: couponRaw,
    locale: window.lhLocale || 'ro',
  });
  var fetchOpts = {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: body,
  };
  if (lhPricePreviewAbort) fetchOpts.signal = lhPricePreviewAbort.signal;
  window
    .fetch(ajaxBookingPricePreview, fetchOpts)
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      if (!data || !data.success) return;
      data._syncKey = reqKey;
      lhLastPricePreview = data;
      lhRefreshCouponPricingUiFromPreview();
    })
    .catch(function (e) {
      if (e && e.name === 'AbortError') return;
    });
}

function lhRefreshCouponPricingUiFromPreview() {
  if (!fpCheckIn || !fpCheckOut || !fpCheckIn.selectedDates || !fpCheckOut.selectedDates) return;
  var cinD = fpCheckIn.selectedDates[0];
  var coutD = fpCheckOut.selectedDates[0];
  if (!cinD || !coutD) return;
  var nights = (coutD - cinD) / 86400000;
  if (nights < 1) return;
  var cinY = fpCheckIn.formatDate(cinD, 'Y-m-d');
  var coutY = fpCheckOut.formatDate(coutD, 'Y-m-d');
  var p = lhBookingStayPricingEuro(cinY, coutY, lhGetGuestsIntForPricing());
  lhApplyCouponLayerToTotals(p, cinY, coutY, nights);
}

function lhApplyCouponLayerToTotals(pricingSync, cinYmd, coutYmd, nights) {
  var totalPrice = document.getElementById('totalPrice');
  var couponLineEl = document.getElementById('lh-total-coupon-line');
  var hintEl = document.getElementById('lh-coupon-hint');
  var breakdown = document.getElementById('lh-total-breakdown');
  var coup = lhGetCouponRawInput();
  var gInt = lhGetGuestsIntForPricing();
  if (coup === '') {
    if (hintEl) {
      hintEl.textContent = '';
      hintEl.classList.add('hidden');
    }
    if (couponLineEl) {
      couponLineEl.textContent = '';
      couponLineEl.classList.add('hidden');
    }
    if (totalPrice) totalPrice.textContent = pricingSync.total.toFixed(0) + lhCurrencySuffix();
    lhUpdatePaymentMethodAmounts(pricingSync.total);
    lhLastPricePreview = null;
    if (breakdown) {
      var showB =
        pricingSync.baseLine ||
        pricingSync.discountLine ||
        pricingSync.extraGuestMathLine ||
        pricingSync.extraGuestNote;
      if (showB) breakdown.classList.remove('hidden');
    }
    return;
  }
  var key = lhPricePreviewSyncKey(cinYmd, coutYmd, gInt, coup);
  if (!lhLastPricePreview || lhLastPricePreview._syncKey !== key) {
    if (hintEl) {
      hintEl.textContent = typeof lhT === 'function' ? lhT('booking.coupon_checking') : '';
      hintEl.classList.remove('hidden');
      hintEl.className =
        'text-xs text-blue-grey font-medium mb-3 leading-snug';
    }
    if (couponLineEl) couponLineEl.classList.add('hidden');
    if (totalPrice) totalPrice.textContent = pricingSync.total.toFixed(0) + lhCurrencySuffix();
    lhUpdatePaymentMethodAmounts(pricingSync.total);
    return;
  }
  if (lhLastPricePreview.coupon_error) {
    if (hintEl) {
      hintEl.textContent = lhLastPricePreview.coupon_error;
      hintEl.classList.remove('hidden');
      hintEl.className = 'text-xs text-red-700 font-semibold mb-3 leading-snug';
    }
    if (couponLineEl) couponLineEl.classList.add('hidden');
  } else {
    if (hintEl) {
      hintEl.textContent = '';
      hintEl.classList.add('hidden');
    }
    var cd = parseFloat(String(lhLastPricePreview.coupon_discount || '0'));
    if (cd > 0.499 && couponLineEl) {
      couponLineEl.textContent = lhFormatCouponLine(coup, cd);
      couponLineEl.classList.remove('hidden');
    } else if (couponLineEl) {
      couponLineEl.textContent = '';
      couponLineEl.classList.add('hidden');
    }
  }
  var totNum = parseFloat(String(lhLastPricePreview.total));
  var onSiteNum = parseFloat(String(lhLastPricePreview.on_site_total || lhLastPricePreview.total));
  if (isNaN(onSiteNum)) onSiteNum = totNum;
  if (!isNaN(totNum)) lhUpdatePaymentMethodAmounts(onSiteNum);
  if (breakdown) {
    var showB2 =
      pricingSync.baseLine ||
      pricingSync.discountLine ||
      (couponLineEl && !couponLineEl.classList.contains('hidden')) ||
      pricingSync.extraGuestMathLine ||
      pricingSync.extraGuestNote;
    if (showB2) breakdown.classList.remove('hidden');
  }
}

function lhPricePreviewReadyForSubmit(cinYmd, coutYmd, guestsStr) {
  var coup = lhGetCouponRawInput();
  var gInt = parseInt(String(guestsStr), 10) || 1;
  if (coup === '') return true;
  var key = lhPricePreviewSyncKey(cinYmd, coutYmd, gInt, coup);
  if (!lhLastPricePreview || lhLastPricePreview._syncKey !== key) return false;
  return !lhLastPricePreview.coupon_error;
}

function lhToggleExtraGuestNotice() {
  var el = document.getElementById('lh-extra-guest-notice');
  if (!el) return;
  var msg = lhExtraGuestNoticeText(lhGetGuestsIntForPricing());
  var box = document.getElementById('totalBox');
  if (msg && box && !box.classList.contains('hidden')) {
    el.textContent = '';
    el.classList.add('hidden');
    return;
  }
  if (msg) {
    el.textContent = msg;
    el.classList.remove('hidden');
  } else {
    el.textContent = '';
    el.classList.add('hidden');
  }
}

function lhUpdateTotalBoxFromRange(checkInYmd, checkOutYmd, nights) {
  var totalPrice = document.getElementById('totalPrice');
  var box = document.getElementById('totalBox');
  var breakdown = document.getElementById('lh-total-breakdown');
  var baseLineEl = document.getElementById('lh-total-base-line');
  var discountLineEl = document.getElementById('lh-total-discount-line');
  var couponLineEl = document.getElementById('lh-total-coupon-line');
  var extraLineEl = document.getElementById('lh-total-extra-line');
  var extraNoteEl = document.getElementById('lh-total-extra-guest-note');
  var hintElCoupon = document.getElementById('lh-coupon-hint');
  if (!box) return;
  function resetBreakdown() {
    if (baseLineEl) baseLineEl.textContent = '';
    if (discountLineEl) {
      discountLineEl.textContent = '';
      discountLineEl.classList.add('hidden');
    }
    if (couponLineEl) {
      couponLineEl.textContent = '';
      couponLineEl.classList.add('hidden');
    }
    if (hintElCoupon) {
      hintElCoupon.textContent = '';
      hintElCoupon.classList.add('hidden');
    }
    lhLastPricePreview = null;
    if (extraLineEl) {
      extraLineEl.textContent = '';
      extraLineEl.classList.add('hidden');
    }
    if (extraNoteEl) {
      extraNoteEl.textContent = '';
      extraNoteEl.classList.add('hidden');
    }
    if (breakdown) breakdown.classList.add('hidden');
  }
  nights = parseInt(String(nights), 10) || 0;
  var effMin = lhEffectiveMinStay(checkInYmd, checkOutYmd);
  if (nights > 0 && nights < effMin) {
    if (totalPrice) totalPrice.textContent = '';
    resetBreakdown();
    box.classList.add('hidden');
    lhSetDateRangeHintInvalid(lhMinStayTooShortMsg(effMin));
    lhToggleExtraGuestNotice();
    return;
  }
  lhResetDateRangeHint();
  if (nights < 1) {
    if (totalPrice) totalPrice.textContent = '';
    resetBreakdown();
    box.classList.add('hidden');
    lhToggleExtraGuestNotice();
    return;
  }
  var p = lhBookingStayPricingEuro(checkInYmd, checkOutYmd, lhGetGuestsIntForPricing());
  if (baseLineEl) baseLineEl.textContent = p.baseLine || '';
  if (discountLineEl) {
    if (p.discountLine) {
      discountLineEl.textContent = p.discountLine;
      discountLineEl.classList.remove('hidden');
    } else {
      discountLineEl.textContent = '';
      discountLineEl.classList.add('hidden');
    }
  }
  if (extraLineEl) {
    if (p.extraGuestMathLine) {
      extraLineEl.textContent = p.extraGuestMathLine;
      extraLineEl.classList.remove('hidden');
    } else {
      extraLineEl.textContent = '';
      extraLineEl.classList.add('hidden');
    }
  }
  if (extraNoteEl) {
    if (p.extraGuestNote) {
      extraNoteEl.textContent = p.extraGuestNote;
      extraNoteEl.classList.remove('hidden');
    } else {
      extraNoteEl.textContent = '';
      extraNoteEl.classList.add('hidden');
    }
  }
  if (breakdown) {
    if (
      p.baseLine ||
      p.discountLine ||
      p.extraGuestMathLine ||
      p.extraGuestNote ||
      lhGetCouponRawInput() !== ''
    ) {
      breakdown.classList.remove('hidden');
    } else {
      breakdown.classList.add('hidden');
    }
  }
  lhApplyCouponLayerToTotals(p, checkInYmd, checkOutYmd, nights);
  var coupRun = lhGetCouponRawInput();
  if (coupRun !== '') {
    lhScheduleBookingPricePreview(
      checkInYmd,
      checkOutYmd,
      lhGetGuestsIntForPricing(),
      coupRun
    );
  } else {
    window.clearTimeout(lhPricePreviewTimer);
  }
  box.classList.remove('hidden');
  lhToggleExtraGuestNotice();
}

var propertyId = <?= (int)$property['id'] ?>;
var propertyTitle = <?= json_encode((string)($property['title'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
var bookingCsrf = <?= json_encode(lh_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var lhOnlineDiscountPercent = <?= json_encode((float) lh_booking_online_discount_percent(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function lhGetPaymentMethod() {
  var r = document.querySelector('input[name="booking_payment_method"]:checked');
  return r && r.value === 'online' ? 'online' : 'on_site';
}

function lhGetTermsAccepted() {
  var el = document.getElementById('bookingTermsAccepted');
  return !!(el && el.checked);
}

function lhCalcOnlineTotal(onSiteTotal) {
  var pct = parseFloat(String(lhOnlineDiscountPercent)) || 0;
  if (pct <= 0) return onSiteTotal;
  return Math.max(0, Math.round((onSiteTotal - onSiteTotal * pct / 100) * 100) / 100);
}

function lhUpdatePaymentMethodAmounts(onSiteTotal) {
  var onSiteEl = document.getElementById('lh-pay-on-site-amount');
  var onlineEl = document.getElementById('lh-pay-online-amount');
  var onlineTotal = lhCalcOnlineTotal(onSiteTotal);
  var suffix = lhCurrencySuffix();
  if (onSiteEl) onSiteEl.textContent = onSiteTotal.toFixed(0) + suffix;
  if (onlineEl) onlineEl.textContent = onlineTotal.toFixed(0) + suffix;
  lhUpdateReserveButtonLabel();
  lhUpdateMainTotalDisplay(onSiteTotal, onlineTotal);
}

function lhUpdateMainTotalDisplay(onSiteTotal, onlineTotal) {
  var totalPrice = document.getElementById('totalPrice');
  var label = document.getElementById('lh-total-pay-label');
  var onlineLine = document.getElementById('lh-total-online-discount-line');
  if (!totalPrice) return;
  var method = lhGetPaymentMethod();
  var show = method === 'online' ? onlineTotal : onSiteTotal;
  totalPrice.textContent = show.toFixed(0) + lhCurrencySuffix();
  if (label) {
    label.textContent = method === 'online'
      ? (typeof lhT === 'function' ? lhT('booking.payment_due_now') : 'De plată acum:')
      : (typeof lhT === 'function' ? lhT('booking.total_pay') : 'Total de plată:');
  }
  if (onlineLine) {
    if (method === 'online' && lhOnlineDiscountPercent > 0 && onSiteTotal > onlineTotal) {
      onlineLine.textContent = typeof lhT === 'function'
        ? lhT('booking.online_discount_line', { pct: String(lhOnlineDiscountPercent), amount: (onSiteTotal - onlineTotal).toFixed(0) + lhCurrencySuffix() })
        : '';
      onlineLine.classList.remove('hidden');
    } else {
      onlineLine.textContent = '';
      onlineLine.classList.add('hidden');
    }
  }
}

function lhUpdateReserveButtonLabel() {
  var label = document.getElementById('reserveBtnLabel');
  if (!label) return;
  label.textContent = lhGetPaymentMethod() === 'online'
    ? (typeof lhT === 'function' ? lhT('booking.continue_payment') : 'Continuă la plată')
    : <?= json_encode(__('booking.book_now'), JSON_UNESCAPED_UNICODE) ?>;
}

(function () {
  document.querySelectorAll('input[name="booking_payment_method"]').forEach(function (el) {
    el.addEventListener('change', function () {
      var onSite = lhLastPricePreview ? parseFloat(String(lhLastPricePreview.on_site_total || lhLastPricePreview.total || 0)) : parseFloat(String(document.getElementById('totalPrice')?.textContent || 0));
      if (isNaN(onSite)) onSite = 0;
      lhUpdatePaymentMethodAmounts(onSite);
    });
  });
  lhUpdateReserveButtonLabel();
})();

var lhPendingBooking = null;
var lhConfirmModalLastFocus = null;

var preGuests = "<?= htmlspecialchars($guests ?? '') ?>";
if (preGuests) {
  var guestSelect = document.getElementById('guests');
  if (guestSelect && ['1', '2', '3', '4', '5', '6'].indexOf(preGuests) !== -1) {
    guestSelect.value = preGuests;
  }
}
lhToggleExtraGuestNotice();

var totalBox=document.getElementById('totalBox');
var dateHintEl=document.getElementById('lh-date-range-hint');
var lhDateHintDefaultText=dateHintEl?dateHintEl.textContent.trim():'';
var fpCheckIn, fpCheckOut;

function lhSyncDateRangeInstructionVisibility() {
  if (!dateHintEl) return;
  if (
    typeof fpCheckIn === 'undefined' ||
    !fpCheckIn ||
    typeof fpCheckOut === 'undefined' ||
    !fpCheckOut
  ) {
    dateHintEl.classList.remove('hidden');
    return;
  }
  if (dateHintEl.className.indexOf('amber') !== -1) {
    return;
  }
  var cinD = fpCheckIn.selectedDates[0];
  var coutD = fpCheckOut.selectedDates[0];
  if (!cinD || !coutD) {
    dateHintEl.classList.remove('hidden');
    return;
  }
  var nights = (coutD - cinD) / 86400000;
  var cin = fpCheckIn.formatDate(cinD, 'Y-m-d');
  var cout = fpCheckOut.formatDate(coutD, 'Y-m-d');
  var effR = lhEffectiveMinStay(cin, cout);
  if (cout > cin && nights >= effR) {
    dateHintEl.classList.add('hidden');
  } else {
    dateHintEl.classList.remove('hidden');
  }
}

function lhResetDateRangeHint(){
if(!dateHintEl)return;
dateHintEl.className='text-xs text-blue-grey mb-4 leading-snug';
dateHintEl.textContent=lhDateHintDefaultText;
lhSyncDateRangeInstructionVisibility();
}
function lhSetDateRangeHintInvalid(msg){
if(!dateHintEl)return;
dateHintEl.classList.remove('hidden');
dateHintEl.className='text-xs text-amber-800 font-medium mb-4 leading-snug';
dateHintEl.textContent=msg;
}

var preCheckIn  = "<?= $has_checkin ? $check_in : '' ?>";
var preCheckOut = "<?= $has_checkout ? $check_out : '' ?>";

function lhShowToast(message, kind) {
  var el = document.getElementById('lh-booking-toast');
  if (!el) return;
  el.textContent = message;
  el.classList.remove('lh-toast--success', 'lh-toast--error', 'lh-toast--visible');
  el.classList.add(kind === 'error' ? 'lh-toast--error' : 'lh-toast--success');
  requestAnimationFrame(function () {
    el.classList.add('lh-toast--visible');
  });
  clearTimeout(el._lhT);
  el._lhT = setTimeout(function () {
    el.classList.remove('lh-toast--visible');
  }, 4200);
}

function lhSetLoading(isLoading) {
  var btn = document.getElementById('reserveBtn');
  var label = document.getElementById('reserveBtnLabel');
  var spin = document.getElementById('reserveBtnSpinner');
  if (!btn || !label || !spin) return;
  btn.disabled = !!isLoading;
  if (isLoading) {
    label.textContent = typeof lhT === 'function' ? lhT('booking.processing') : '';
    spin.classList.remove('hidden');
  } else {
    spin.classList.add('hidden');
  }
}

function lhGetBookingConfirmFocusables() {
  var panel = document.querySelector('.lh-booking-confirm-panel');
  if (!panel) return [];
  return Array.prototype.slice
    .call(panel.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
    .filter(function (el) {
      return panel.contains(el) && (el.offsetParent !== null || el.getClientRects().length > 0);
    });
}

function lhBookingConfirmKeydown(e) {
  var root = document.getElementById('lh-booking-confirm-root');
  if (!root || root.hasAttribute('hidden')) return;
  if (e.key === 'Escape') {
    e.preventDefault();
    e.stopPropagation();
    lhCloseBookingConfirmModal();
    return;
  }
  if (e.key !== 'Tab') return;
  var focusables = lhGetBookingConfirmFocusables();
  if (focusables.length === 0) return;
  e.preventDefault();
  var first = focusables[0];
  var last = focusables[focusables.length - 1];
  var idx = focusables.indexOf(document.activeElement);
  if (e.shiftKey) {
    if (idx <= 0) last.focus();
    else focusables[idx - 1].focus();
  } else {
    if (idx === -1 || idx >= focusables.length - 1) first.focus();
    else focusables[idx + 1].focus();
  }
}

function lhCloseBookingConfirmModal() {
  var root = document.getElementById('lh-booking-confirm-root');
  if (!root || root.hasAttribute('hidden')) return;
  root.setAttribute('hidden', '');
  root.setAttribute('aria-hidden', 'true');
  root.classList.remove('lh-booking-confirm-root--open');
  document.removeEventListener('keydown', lhBookingConfirmKeydown, true);
  lhPendingBooking = null;
  if (!lhOpenBookingConfirmModal._hadSheet) {
    document.body.classList.remove('overflow-hidden');
  }
  lhOpenBookingConfirmModal._hadSheet = false;
  lhFocusNoScroll(lhConfirmModalLastFocus);
  lhConfirmModalLastFocus = null;
}

function lhOpenBookingConfirmModal(payload) {
  lhPendingBooking = payload;
  var sheet = document.getElementById('lh-booking-sheet');
  var sheetActive = sheet && sheet.getAttribute('aria-hidden') !== 'true';
  lhOpenBookingConfirmModal._hadSheet = !!sheetActive;
  if (!sheetActive) {
    document.body.classList.add('overflow-hidden');
  }

  document.getElementById('lh-confirm-property').textContent = propertyTitle;
  document.getElementById('lh-confirm-period').textContent =
    fpCheckIn.formatDate(payload.dateFrom, lhBookingAltFormat) +
    ' → ' +
    fpCheckOut.formatDate(payload.dateTo, lhBookingAltFormat);
  var gStr = String(payload.guests);
  var gDisp =
    gStr === '6'
      ? '6+ ' + (typeof lhT === 'function' ? lhT('booking.guests_plural') : 'guests')
      : gStr === '1'
        ? '1 ' + (typeof lhT === 'function' ? lhT('booking.guest_singular') : 'guest')
        : gStr + ' ' + (typeof lhT === 'function' ? lhT('booking.guests_plural') : 'guests');
  document.getElementById('lh-confirm-guests').textContent = gDisp;
  var breakWrap = document.getElementById('lh-confirm-price-break');
  var cBase = document.getElementById('lh-confirm-base-line');
  var cDisc = document.getElementById('lh-confirm-discount-line');
  var cCup = document.getElementById('lh-confirm-coupon-line');
  var cExtra = document.getElementById('lh-confirm-extra-line');
  var cNote = document.getElementById('lh-confirm-extra-note');
  var bl = typeof payload.pricingBaseLine === 'string' ? payload.pricingBaseLine : '';
  var dl = typeof payload.pricingDiscountLine === 'string' ? payload.pricingDiscountLine : '';
  var cl = typeof payload.pricingCouponLine === 'string' ? payload.pricingCouponLine : '';
  var eln = typeof payload.pricingExtraLine === 'string' ? payload.pricingExtraLine : '';
  var en = typeof payload.pricingExtraNote === 'string' ? payload.pricingExtraNote : '';
  if (breakWrap && cBase) {
    cBase.textContent = bl;
    if (cDisc) {
      if (dl) {
        cDisc.textContent = dl;
        cDisc.classList.remove('hidden');
      } else {
        cDisc.textContent = '';
        cDisc.classList.add('hidden');
      }
    }
    if (cCup) {
      if (cl) {
        cCup.textContent = cl;
        cCup.classList.remove('hidden');
      } else {
        cCup.textContent = '';
        cCup.classList.add('hidden');
      }
    }
    if (cExtra) {
      if (eln) {
        cExtra.textContent = eln;
        cExtra.classList.remove('hidden');
      } else {
        cExtra.textContent = '';
        cExtra.classList.add('hidden');
      }
    }
    if (cNote) {
      if (en) {
        cNote.textContent = en;
        cNote.classList.remove('hidden');
      } else {
        cNote.textContent = '';
        cNote.classList.add('hidden');
      }
    }
    if (bl || dl || cl || eln || en) breakWrap.classList.remove('hidden');
    else breakWrap.classList.add('hidden');
  }
  document.getElementById('lh-confirm-total').textContent =
    (payload.displayTotal ? payload.displayTotal.toFixed(0) : (payload.totalEuro ? payload.totalEuro.toFixed(0) : '0')) + lhCurrencySuffix();
  var payMethodEl = document.getElementById('lh-confirm-payment-method');
  if (payMethodEl) {
    payMethodEl.textContent = payload.paymentMethod === 'online'
      ? (typeof lhT === 'function' ? lhT('booking.payment_online') : 'Plată online')
      : (typeof lhT === 'function' ? lhT('booking.payment_on_site') : 'Plată la check-in');
  }
  document.getElementById('lh-confirm-name').textContent = payload.guestName;
  document.getElementById('lh-confirm-phone').textContent = payload.guestPhone;
  document.getElementById('lh-confirm-email').textContent = payload.guestEmail;

  var root = document.getElementById('lh-booking-confirm-root');
  root.removeAttribute('hidden');
  root.setAttribute('aria-hidden', 'false');
  root.classList.add('lh-booking-confirm-root--open');
  lhConfirmModalLastFocus = document.activeElement;
  document.addEventListener('keydown', lhBookingConfirmKeydown, true);
  requestAnimationFrame(function () {
    var submitBtn = document.getElementById('lh-booking-confirm-submit');
    if (submitBtn) submitBtn.focus();
  });
}

function lhShowBookingSuccessBanner(bookingId, email) {
  var banner = document.getElementById('lh-booking-success-banner');
  var text = document.getElementById('lh-booking-success-text');
  if (!banner || !text) return;
  text.textContent = typeof lhT === 'function'
    ? lhT('booking.success_banner', { email: email, id: String(bookingId) })
    : email;
  banner.removeAttribute('hidden');
  requestAnimationFrame(function () {
    banner.classList.add('lh-booking-success-banner--visible');
  });
}

function lhHideBookingSuccessBanner() {
  var banner = document.getElementById('lh-booking-success-banner');
  if (!banner) return;
  banner.classList.remove('lh-booking-success-banner--visible');
  setTimeout(function () {
    if (!banner.classList.contains('lh-booking-success-banner--visible')) {
      banner.setAttribute('hidden', '');
    }
  }, 320);
}

function lhExecuteBookingRequest(payload) {
  var btn = document.getElementById('reserveBtn');
  var msg = document.getElementById('availabilityMsg');
  lhSetLoading(true);
  if (msg) {
    msg.innerHTML = '';
    msg.className = 'text-xs text-center mt-3 text-blue-grey';
  }

  fetch(ajaxCreateBooking, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: new URLSearchParams({
      csrf_token: bookingCsrf,
      company: document.getElementById('bookingCompany') ? document.getElementById('bookingCompany').value : '',
      property_id: propertyId,
      guest_name: payload.guestName,
      guest_phone: payload.guestPhone,
      guest_email: payload.guestEmail,
      check_in: payload.checkin,
      check_out: payload.checkout,
      guests: payload.guests,
      coupon_code: payload.couponCode || '',
      locale: window.lhLocale || 'ro',
      payment_method: payload.paymentMethod || 'on_site',
      terms_accepted: '1',
    }),
  })
    .then(function (r) {
      return r.json();
    })
    .then(function (resp) {
      lhSetLoading(false);
      var label = document.getElementById('reserveBtnLabel');
      if (resp.success) {
        if (resp.payment_method === 'online' && resp.checkout_url) {
          window.location.href = resp.checkout_url;
          return;
        }
        if (msg) {
          msg.innerHTML = '';
          msg.className = 'text-xs text-center mt-3 text-blue-grey';
        }
        if (label) label.textContent = typeof lhT === 'function' ? lhT('booking.confirmed') : '';
        btn.disabled = true;
        lhShowBookingSuccessBanner(resp.booking_id, payload.guestEmail);
      } else {
        if (msg) {
          msg.innerHTML = resp.message || (typeof lhT === 'function' ? lhT('booking.generic_error') : '');
          msg.className = 'text-xs text-center mt-3 font-semibold text-red-800';
        }
        if (label) label.textContent = <?= json_encode(__('booking.book_now'), JSON_UNESCAPED_UNICODE) ?>;
        btn.disabled = false;
        lhShowToast(resp.message || (typeof lhT === 'function' ? lhT('booking.generic_error') : ''), 'error');
      }
    })
    .catch(function () {
      lhSetLoading(false);
      var label = document.getElementById('reserveBtnLabel');
      if (msg) {
        msg.innerHTML = typeof lhT === 'function' ? lhT('errors.network') : '';
        msg.className = 'text-xs text-center mt-3 font-semibold text-red-800';
      }
      if (label) label.textContent = <?= json_encode(__('booking.book_now'), JSON_UNESCAPED_UNICODE) ?>;
      btn.disabled = false;
      lhShowToast(typeof lhT === 'function' ? lhT('errors.network') : '', 'error');
    });
}

/** Intervale blocked_dates: [from, to) half-open, ca în create_booking / iCal. */
var lhBookingBlockedRanges = [];

/** Flatpickr apelează predicatele disable ca d(date) fără this = instanță. */
function lhLocalDateToYmd(d) {
  if (!d || typeof d.getFullYear !== 'function') return '';
  var y = d.getFullYear();
  var m = String(d.getMonth() + 1).padStart(2, '0');
  var day = String(d.getDate()).padStart(2, '0');
  return y + '-' + m + '-' + day;
}

function lhBookingStayOverlapsBlocked(checkInYmd, checkOutYmd) {
  var ranges = lhBookingBlockedRanges;
  if (!ranges || !ranges.length) return false;
  for (var i = 0; i < ranges.length; i++) {
    var r = ranges[i];
    var from = r && r.from;
    var to = r && r.to;
    if (!from || !to) continue;
    if (from < checkOutYmd && to > checkInYmd) return true;
  }
  return false;
}

function lhYmdCannotBeCheckIn(ymd) {
  var ranges = lhBookingBlockedRanges;
  if (!ranges || !ranges.length) return false;
  for (var j = 0; j < ranges.length; j++) {
    var b = ranges[j];
    var f = b && b.from;
    var t = b && b.to;
    if (!f || !t) continue;
    if (f <= ymd && ymd < t) return true;
  }
  return false;
}

function lhNightsFromCheckInToCheckoutYmd(checkInYmd, checkOutYmd) {
  var a = String(checkInYmd).split('-');
  var b = String(checkOutYmd).split('-');
  if (a.length !== 3 || b.length !== 3) return 0;
  var d0 = new Date(parseInt(a[0], 10), parseInt(a[1], 10) - 1, parseInt(a[2], 10));
  var d1 = new Date(parseInt(b[0], 10), parseInt(b[1], 10) - 1, parseInt(b[2], 10));
  return Math.round((d1 - d0) / 86400000);
}

function lhCheckoutInvalidForBooking(checkInYmd, checkOutYmd) {
  if (!checkOutYmd || !checkInYmd || checkOutYmd <= checkInYmd) return true;
  var need = lhEffectiveMinStay(checkInYmd, checkOutYmd);
  if (lhNightsFromCheckInToCheckoutYmd(checkInYmd, checkOutYmd) < need) return true;
  return lhBookingStayOverlapsBlocked(checkInYmd, checkOutYmd);
}

function lhBookingFpBindLegend(fp, legendId) {
  if (!fp || !legendId) return;
  if (fp.input) fp.input.setAttribute('aria-labelledby', legendId);
  if (fp.altInput) fp.altInput.setAttribute('aria-labelledby', legendId);
}

function lhBookingCheckInDisableFn(date) {
  var ymd = lhLocalDateToYmd(date);
  return ymd ? lhYmdCannotBeCheckIn(ymd) : false;
}

function lhBookingCheckOutDisableFn(date) {
  var ymd = lhLocalDateToYmd(date);
  if (!ymd) return false;
  var cinEl = document.getElementById('booking-check-in');
  var cinInst = cinEl && cinEl._flatpickr;
  var cinD = cinInst && cinInst.selectedDates && cinInst.selectedDates[0];
  if (!cinD) {
    return lhYmdCannotBeCheckIn(ymd);
  }
  var cinYmd = lhLocalDateToYmd(cinD);
  if (!cinYmd) return lhYmdCannotBeCheckIn(ymd);
  return lhCheckoutInvalidForBooking(cinYmd, ymd);
}

function lhBookingOnDayCreateCheckIn(_dObj, _dStr, fpInst, dayElem) {
  var prev = dayElem.querySelector('.lh-cal-day-price');
  if (prev) prev.remove();
  if (!dayElem.dateObj) return;
  var ymd = fpInst.formatDate(dayElem.dateObj, 'Y-m-d');
  var rate = lhNightRateEuroForYmd(ymd);
  var span = document.createElement('span');
  span.className = 'lh-cal-day-price';
  span.textContent = Math.round(rate) + lhCurrencySuffix();
  dayElem.appendChild(span);
}

function lhBookingOnDayCreateCheckOut(_dObj, _dStr, fpInst, dayElem) {
  var prev = dayElem.querySelector('.lh-cal-day-price');
  if (prev) prev.remove();
  dayElem.classList.remove('lh-cal-checkout-only');
  if (!dayElem.dateObj) return;
  var ymd = fpInst.formatDate(dayElem.dateObj, 'Y-m-d');
  var rate = lhNightRateEuroForYmd(ymd);
  var span = document.createElement('span');
  span.className = 'lh-cal-day-price';
  span.textContent = Math.round(rate) + lhCurrencySuffix();
  dayElem.appendChild(span);
  var cinEl = document.getElementById('booking-check-in');
  var cinInst = cinEl && cinEl._flatpickr;
  var s = cinInst && cinInst.selectedDates ? cinInst.selectedDates : [];
  if (s.length === 1) {
    var cin0 = cinInst.formatDate(s[0], 'Y-m-d');
    if (
      ymd > cin0 &&
      lhYmdCannotBeCheckIn(ymd) &&
      !lhCheckoutInvalidForBooking(cin0, ymd)
    ) {
      dayElem.classList.add('lh-cal-checkout-only');
    }
  }
}

function lhRepositionBookingFlatpickr() {
  ['booking-check-in', 'booking-check-out'].forEach(function (id) {
    var el = document.getElementById(id);
    var inst = el && el._flatpickr;
    if (inst && inst.isOpen && typeof inst._positionCalendar === 'function') {
      inst._positionCalendar();
    }
  });
}

function lhBookingAttachScrollForPicker() {
  window.addEventListener('scroll', lhRepositionBookingFlatpickr, true);
  var sheetBody = document.getElementById('lh-booking-sheet-body');
  if (sheetBody) {
    sheetBody.addEventListener('scroll', lhRepositionBookingFlatpickr, { passive: true });
  }
}

function lhBookingDetachScrollForPicker() {
  window.removeEventListener('scroll', lhRepositionBookingFlatpickr, true);
  var sheetBody = document.getElementById('lh-booking-sheet-body');
  if (sheetBody) sheetBody.removeEventListener('scroll', lhRepositionBookingFlatpickr);
}

function lhBookingOnDatesChanged() {
  if (!fpCheckIn || !fpCheckOut) return;
  var cinD = fpCheckIn.selectedDates[0];
  var coutD = fpCheckOut.selectedDates[0];
  if (cinD && coutD) {
    var nights = (coutD - cinD) / 86400000;
    var cinCh = fpCheckIn.formatDate(cinD, 'Y-m-d');
    var coutCh = fpCheckOut.formatDate(coutD, 'Y-m-d');
    if (nights > 0) {
      lhUpdateTotalBoxFromRange(cinCh, coutCh, nights);
    } else {
      totalBox.classList.add('hidden');
      lhSetDateRangeHintInvalid(lhMinStayTooShortMsg(lhEffectiveMinStay(cinCh, coutCh)));
      lhToggleExtraGuestNotice();
    }
  } else {
    totalBox.classList.add('hidden');
    lhResetDateRangeHint();
    lhToggleExtraGuestNotice();
  }
  lhSyncDateRangeInstructionVisibility();
}

var lhBookingFpLocale = (function () {
  var loc = <?= json_encode($lhPdFpJs, JSON_UNESCAPED_UNICODE) ?>;
  if (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns[loc]) {
    return Object.assign({}, flatpickr.l10ns[loc], { firstDayOfWeek: 1 });
  }
  return { firstDayOfWeek: 1 };
})();

fpCheckOut = flatpickr('#booking-check-out', {
  locale: lhBookingFpLocale,
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: lhBookingAltFormat,
  minDate: (function () {
    if (!preCheckIn) return 'today';
    var d = new Date(preCheckIn + 'T12:00:00');
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1);
  })(),
  disableMobile: true,
  disable: [lhBookingCheckOutDisableFn],
  defaultDate: preCheckOut || null,
  clickOpens: !!preCheckIn,
  onReady: function (_d, _s, instance) {
    lhBookingFpBindLegend(instance, 'lh-booking-checkout-label');
    if (preCheckIn) {
      var d0 = new Date(preCheckIn + 'T12:00:00');
      var nextMin = new Date(d0.getFullYear(), d0.getMonth(), d0.getDate() + 1);
      instance.set('minDate', nextMin);
    }
  },
  onDayCreate: lhBookingOnDayCreateCheckOut,
  onOpen: function () {
    lhBookingAttachScrollForPicker();
  },
  onClose: function () {
    lhBookingDetachScrollForPicker();
    lhBookingOnDatesChanged();
  },
  onChange: function () {
    lhBookingOnDatesChanged();
  },
});

fpCheckIn = flatpickr('#booking-check-in', {
  locale: lhBookingFpLocale,
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: lhBookingAltFormat,
  minDate: 'today',
  disableMobile: true,
  disable: [lhBookingCheckInDisableFn],
  defaultDate: preCheckIn || null,
  onReady: function (_selectedDates, _dateStr, instance) {
    lhBookingFpBindLegend(instance, 'lh-booking-checkin-label');
    fetch(ajaxBookedDates + '?property_id=' + propertyId)
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        lhBookingBlockedRanges = data && data.blocked_ranges ? data.blocked_ranges : [];
        instance.set('disable', [lhBookingCheckInDisableFn]);
        fpCheckOut.set('disable', [lhBookingCheckOutDisableFn]);
        if (instance.redraw) instance.redraw();
        if (fpCheckOut.redraw) fpCheckOut.redraw();
      })
      .catch(function () {
        lhBookingBlockedRanges = [];
        instance.set('disable', [lhBookingCheckInDisableFn]);
        fpCheckOut.set('disable', [lhBookingCheckOutDisableFn]);
        if (instance.redraw) instance.redraw();
        if (fpCheckOut.redraw) fpCheckOut.redraw();
      });
    if (preCheckIn && preCheckOut) {
      var nightsPre = (new Date(preCheckOut) - new Date(preCheckIn)) / 86400000;
      if (nightsPre > 0) {
        lhUpdateTotalBoxFromRange(preCheckIn, preCheckOut, nightsPre);
      } else if (nightsPre <= 0) {
        totalBox.classList.add('hidden');
        lhSetDateRangeHintInvalid(lhMinStayTooShortMsg(lhEffectiveMinStay(preCheckIn, preCheckOut)));
        lhToggleExtraGuestNotice();
      }
    }
  },
  onDayCreate: lhBookingOnDayCreateCheckIn,
  onOpen: function () {
    lhBookingAttachScrollForPicker();
  },
  onClose: function (selectedDates) {
    lhBookingDetachScrollForPicker();
    if (!selectedDates[0]) {
      fpCheckOut.clear();
      fpCheckOut.set('minDate', 'today');
      fpCheckOut.set('clickOpens', false);
      fpCheckOut.set('disable', [lhBookingCheckOutDisableFn]);
      lhBookingOnDatesChanged();
      return;
    }
    var cin = selectedDates[0];
    var nextMin = new Date(cin.getFullYear(), cin.getMonth(), cin.getDate() + 1);
    fpCheckOut.set('minDate', nextMin);
    var co = fpCheckOut.selectedDates[0];
    if (co && co <= cin) {
      fpCheckOut.clear();
    } else if (co) {
      var cinY = lhLocalDateToYmd(cin);
      var coY = lhLocalDateToYmd(co);
      if (lhCheckoutInvalidForBooking(cinY, coY)) {
        fpCheckOut.clear();
      }
    }
    fpCheckOut.set('disable', [lhBookingCheckOutDisableFn]);
    fpCheckOut.set('clickOpens', true);
    lhBookingOnDatesChanged();
    fpCheckOut.open();
  },
  onChange: function (selectedDates) {
    if (!selectedDates.length) {
      fpCheckOut.clear();
      fpCheckOut.set('minDate', 'today');
      fpCheckOut.set('clickOpens', false);
      fpCheckOut.set('disable', [lhBookingCheckOutDisableFn]);
    }
    lhBookingOnDatesChanged();
  },
});

lhSyncDateRangeInstructionVisibility();

(function () {
  var guestSelectForPricing = document.getElementById('guests');
  if (!guestSelectForPricing) return;
  guestSelectForPricing.addEventListener('change', function () {
    var cinD = fpCheckIn.selectedDates[0];
    var coutD = fpCheckOut.selectedDates[0];
    if (cinD && coutD) {
      var nights = (coutD - cinD) / 86400000;
      if (nights > 0) {
        lhUpdateTotalBoxFromRange(
          fpCheckIn.formatDate(cinD, 'Y-m-d'),
          fpCheckOut.formatDate(coutD, 'Y-m-d'),
          nights
        );
        return;
      }
    }
    lhToggleExtraGuestNotice();
  });
})();

(function () {
  var coupIn = document.getElementById('booking-coupon-code');
  if (!coupIn || !fpCheckIn || !fpCheckOut) return;
  coupIn.addEventListener('input', function () {
    var cinD = fpCheckIn.selectedDates[0];
    var coutD = fpCheckOut.selectedDates[0];
    if (cinD && coutD) {
      var nx = (coutD - cinD) / 86400000;
      if (nx > 0) {
        lhUpdateTotalBoxFromRange(
          fpCheckIn.formatDate(cinD, 'Y-m-d'),
          fpCheckOut.formatDate(coutD, 'Y-m-d'),
          nx
        );
      }
    }
  });
})();

var btn=document.getElementById('reserveBtn');

btn.onclick=function(){

var cinD = fpCheckIn.selectedDates[0];
var coutD = fpCheckOut.selectedDates[0];
var msg=document.getElementById('availabilityMsg');

if(!cinD || !coutD){
msg.innerHTML=typeof lhT==='function'?lhT('booking.select_period'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('booking.select_period'):'', 'error');
return;
}

var checkin=fpCheckIn.formatDate(cinD,"Y-m-d");
var checkout=fpCheckOut.formatDate(coutD,"Y-m-d");
var nightsEarly=(coutD-cinD)/86400000;
if(checkout<=checkin||nightsEarly<1){
msg.innerHTML=typeof lhT==='function'?lhT('booking.min_one_night'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('search.min_night_hint'):'', 'error');
return;
}
var effBtn = lhEffectiveMinStay(checkin, checkout);
if(nightsEarly < effBtn){
msg.innerHTML = effBtn === 1 ? lhT('api.min_one_night') : lhT('booking.min_stay_extend', { n: effBtn });
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(lhMinStayTooShortMsg(effBtn), 'error');
return;
}

var guestName=document.getElementById('guestName').value.trim();
var guestPhone=document.getElementById('guestPhone').value.trim();
var guestEmail=document.getElementById('guestEmail').value.trim();

if(!guestName){
msg.innerHTML=typeof lhT==='function'?lhT('booking.fill_name'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('booking.fill_name'):'', 'error');
return;
}

if(!guestPhone){
msg.innerHTML=typeof lhT==='function'?lhT('booking.fill_phone'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('booking.fill_phone'):'', 'error');
return;
}

if(!guestEmail){
msg.innerHTML=typeof lhT==='function'?lhT('booking.fill_email'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('booking.fill_email'):'', 'error');
return;
}

if(!lhGetTermsAccepted()){
msg.innerHTML=typeof lhT==='function'?lhT('booking.terms_required'):'';
msg.className="text-xs text-center mt-3 font-semibold text-red-800";
lhShowToast(typeof lhT==='function'?lhT('booking.terms_required'):'', 'error');
return;
}

var guests=document.getElementById('guests').value;
var nights=nightsEarly;
var gInt = parseInt(String(guests), 10) || 1;
var pr = nights > 0 ? lhBookingStayPricingEuro(checkin, checkout, gInt) : { total: 0, baseLine: '', discountLine: '', extraGuestMathLine: '', extraGuestNote: '' };

var coupReserve = lhGetCouponRawInput();
if (coupReserve !== '' && !lhPricePreviewReadyForSubmit(checkin, checkout, guests)) {
  msg.innerHTML = typeof lhT==='function'?lhT('booking.coupon_wait'):'';
  msg.className = 'text-xs text-center mt-3 font-semibold text-red-800';
  lhShowToast(typeof lhT==='function'?lhT('booking.coupon_fix'):'', 'error');
  return;
}
var totalEuroRes = pr.total;
var displayTotalRes = totalEuroRes;
var pricingCouponLineRes = '';
if (coupReserve !== '' && lhLastPricePreview) {
  var tNv = parseFloat(String(lhLastPricePreview.total));
  if (!isNaN(tNv)) totalEuroRes = tNv;
  var cdNv = parseFloat(String(lhLastPricePreview.coupon_discount || 0));
  if (cdNv > 0.499) {
    pricingCouponLineRes = lhFormatCouponLine(coupReserve, cdNv);
  }
}
var paymentMethod = lhGetPaymentMethod();
var onSiteTotal = lhLastPricePreview && lhLastPricePreview.on_site_total != null
  ? parseFloat(String(lhLastPricePreview.on_site_total))
  : totalEuroRes;
if (isNaN(onSiteTotal)) onSiteTotal = totalEuroRes;
displayTotalRes = paymentMethod === 'online' ? lhCalcOnlineTotal(onSiteTotal) : onSiteTotal;

lhOpenBookingConfirmModal({
guestName:guestName,
guestPhone:guestPhone,
guestEmail:guestEmail,
checkin:checkin,
checkout:checkout,
guests:guests,
nights:nights,
totalEuro: totalEuroRes,
displayTotal: displayTotalRes,
paymentMethod: paymentMethod,
pricingBaseLine: pr.baseLine || '',
pricingDiscountLine: pr.discountLine || '',
pricingCouponLine: pricingCouponLineRes,
pricingExtraLine: pr.extraGuestMathLine || '',
pricingExtraNote: pr.extraGuestNote || '',
couponCode: coupReserve,
dateFrom:cinD,
dateTo:coutD
});

};

(function () {
  var overlay = document.getElementById('lh-booking-confirm-overlay');
  var back = document.getElementById('lh-booking-confirm-back');
  var submit = document.getElementById('lh-booking-confirm-submit');
  var successClose = document.getElementById('lh-booking-success-close');
  if (overlay) overlay.addEventListener('click', lhCloseBookingConfirmModal);
  if (back) back.addEventListener('click', lhCloseBookingConfirmModal);
  if (submit) {
    submit.addEventListener('click', function () {
      var payload = lhPendingBooking;
      if (!payload) return;
      lhCloseBookingConfirmModal();
      lhExecuteBookingRequest(payload);
    });
  }
  if (successClose) successClose.addEventListener('click', lhHideBookingSuccessBanner);
})();

(function () {
  /** Coloană sticky desktop doar când lg ȘI înălțime viewport ≥ această valoare (px). Sub prag: bară + sheet (vezi max-height: 760px în CSS). */
  var LH_PD_BOOKING_MIN_VIEWPORT_HEIGHT_PX = 761;

  function lhPdUseDesktopBookingColumn() {
    return window.matchMedia(
      '(min-width: 1024px) and (min-height: ' + LH_PD_BOOKING_MIN_VIEWPORT_HEIGHT_PX + 'px)'
    ).matches;
  }

  var widget = document.getElementById('lh-booking-widget');
  var desktopSlot = document.getElementById('lh-booking-desktop-slot');
  var sheetBody = document.getElementById('lh-booking-sheet-body');
  var sheet = document.getElementById('lh-booking-sheet');
  var overlay = document.getElementById('lh-booking-overlay');
  var openBtn = document.getElementById('lh-open-booking-sheet');
  var closeBtn = document.getElementById('lh-close-booking-sheet');
  if (!widget || !desktopSlot || !sheetBody || !sheet || !overlay) return;

  var lastFocus = null;

  function isSheetOpen() {
    return sheetBody.contains(widget);
  }

  function getFocusables() {
    return sheet.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
  }

  function openBookingSheet() {
    if (lhPdUseDesktopBookingColumn()) return;
    if (isSheetOpen()) return;
    lastFocus = document.activeElement;
    sheetBody.appendChild(widget);
    overlay.classList.remove('opacity-0', 'pointer-events-none');
    overlay.setAttribute('aria-hidden', 'false');
    sheet.classList.remove('translate-y-full');
    sheet.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
    lhRefreshLucide();
    var focusables = getFocusables();
    if (focusables.length) {
      try {
        focusables[0].focus({ preventScroll: true });
      } catch (err) {
        focusables[0].focus();
      }
    }
    document.addEventListener('keydown', onKeydown);
  }

  function closeBookingSheet() {
    if (!isSheetOpen()) return;
    desktopSlot.appendChild(widget);
    overlay.classList.add('opacity-0', 'pointer-events-none');
    overlay.setAttribute('aria-hidden', 'true');
    sheet.classList.add('translate-y-full');
    sheet.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    lhRefreshLucide();
    document.removeEventListener('keydown', onKeydown);
    lhFocusNoScroll(lastFocus);
    lastFocus = null;
  }

  function onKeydown(e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      closeBookingSheet();
      return;
    }
    if (e.key !== 'Tab' || !isSheetOpen()) return;
    var focusables = Array.prototype.slice.call(getFocusables()).filter(function (el) {
      return el.offsetParent !== null || el === document.activeElement;
    });
    if (!focusables.length) return;
    var first = focusables[0];
    var last = focusables[focusables.length - 1];
    if (e.shiftKey) {
      if (document.activeElement === first) {
        e.preventDefault();
        last.focus();
      }
    } else {
      if (document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }

  if (openBtn) openBtn.addEventListener('click', openBookingSheet);
  if (closeBtn) closeBtn.addEventListener('click', closeBookingSheet);
  overlay.addEventListener('click', closeBookingSheet);

  window.addEventListener('resize', function () {
    if (lhPdUseDesktopBookingColumn() && isSheetOpen()) {
      desktopSlot.appendChild(widget);
      overlay.classList.add('opacity-0', 'pointer-events-none');
      overlay.setAttribute('aria-hidden', 'true');
      sheet.classList.add('translate-y-full');
      sheet.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('overflow-hidden');
      document.removeEventListener('keydown', onKeydown);
      lhRefreshLucide();
    }
  });
})();
</script>

