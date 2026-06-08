<style>
  /*
   * Desktop îngust pe verticală: același UX ca mobil (bară + sheet). Coloana desktop (viewport înalt)
   * folosește sticky fără max-height — widgetul crește cu conținutul; derularea e doar pe pagină.
   * LH_PD_BOOKING_MIN_VIEWPORT_HEIGHT_PX (761): de la această înălțime în sus se afișează coloana desktop.
   */
  @media (min-width: 1024px) and (max-height: 760px) {
    #lh-pd-main-wrap {
      padding-bottom: 7rem;
    }
    #lh-pd-main-col {
      grid-column: 1 / -1;
    }
    #lh-booking-desktop-col {
      display: none !important;
    }
    #lh-pd-back-link {
      display: inline-block !important;
    }
    #lh-booking-mobile-bar {
      display: block !important;
    }
    #lh-booking-overlay {
      display: block !important;
    }
    #lh-booking-sheet {
      display: flex !important;
      left: 0;
      right: 0;
      margin-left: auto;
      margin-right: auto;
      max-width: 32rem;
      width: calc(100% - 2rem);
    }
  }

  #lh-gallery-lightbox .lh-gallery-lightbox-backdrop {
    background: rgba(0, 0, 0, 0.88);
    -webkit-backdrop-filter: blur(18px);
    backdrop-filter: blur(18px);
  }

  #lh-gallery-lightbox .lh-gallery-lightbox-swiper .swiper-slide {
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    background: rgb(12 12 12);
    overflow: hidden;
  }

  #lh-gallery-lightbox .lh-gallery-lightbox-swiper .swiper-zoom-container {
    width: 100%;
    height: 100%;
    min-height: 0;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
  }

  #lh-gallery-lightbox .lh-gallery-lightbox-swiper .swiper-zoom-container img {
    max-width: 100%;
    max-height: min(85dvh, 85vh);
    width: auto;
    height: auto;
    object-fit: contain;
    position: relative;
    z-index: 1;
  }

  #lh-gallery-lightbox .lh-lb-zoom-tools {
    pointer-events: auto;
  }

  #lh-gallery-lightbox .lh-gallery-lightbox-swiper .swiper-pagination-fraction {
    color: rgba(255, 255, 255, 0.92);
    font-weight: 600;
    font-size: 0.875rem;
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
  }

  /*
   * Tailwind .flex pe același nod cu [hidden] poate suprascrie display:none → lightbox vizibil la load,
   * iar lhCloseGalleryLightbox() iese devreme (hasAttribute('hidden') rămâne true). Forțăm ascunderea.
   */
  #lh-gallery-lightbox[hidden] {
    display: none !important;
    pointer-events: none !important;
  }

  /* Galerie: miniaturi în 2 rânduri, scroll orizontal (property-details) */
  #lh-pd-thumbs {
    --lh-pd-thumb-w: 4.5rem;
    --lh-pd-thumb-h: 3.375rem;
    --lh-pd-thumb-gap-x: 0.5rem;
    --lh-pd-thumb-gap-y: 0.5rem;
  }
  @media (min-width: 640px) {
    #lh-pd-thumbs {
      --lh-pd-thumb-w: 5.25rem;
      --lh-pd-thumb-h: 4rem;
      --lh-pd-thumb-gap-x: 0.625rem;
      --lh-pd-thumb-gap-y: 0.625rem;
    }
  }
  @media (min-width: 768px) {
    #lh-pd-thumbs {
      --lh-pd-thumb-w: 6rem;
      --lh-pd-thumb-h: 5rem;
    }
  }
  #lh-pd-thumbs .lh-pd-thumbs-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    scroll-snap-type: x proximity;
  }
  #lh-pd-thumbs .lh-pd-thumbs-grid {
    display: grid;
    grid-template-rows: repeat(2, var(--lh-pd-thumb-h));
    grid-auto-flow: column;
    grid-auto-columns: var(--lh-pd-thumb-w);
    column-gap: var(--lh-pd-thumb-gap-x);
    row-gap: var(--lh-pd-thumb-gap-y);
    width: max-content;
    min-height: calc(var(--lh-pd-thumb-h) * 2 + var(--lh-pd-thumb-gap-y));
  }
  #lh-pd-thumbs .lh-pd-thumbs-cell {
    scroll-snap-align: start;
    min-width: 0;
    min-height: 0;
  }
  #lh-pd-thumbs .lh-pd-thumbs-cell--active {
    box-shadow: 0 0 0 2px rgb(var(--color-cta) / 0.95);
  }

  /* Flatpickr (rezervare): preț sub zi + celule mai înalte */
  .flatpickr-day {
    min-height: 2.85rem;
    height: auto;
    max-height: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding-top: 0.2rem;
    line-height: 1.15;
  }
  /* Widget total (#totalBox): rând final total; font scalat cu lățimea containerului (cqi) */
  #totalBox {
    container-type: inline-size;
    container-name: lhBookTotal;
  }
  #totalBox .lh-total-pricing-row {
    display: flex;
    flex-flow: row nowrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    min-width: 0;
  }
  #totalBox .lh-total-pricing-label {
    flex-shrink: 0;
    white-space: nowrap;
  }
  #totalBox .lh-total-pricing-value {
    min-width: 0;
    text-align: right;
    white-space: nowrap;
    line-height: 1.15;
  }
  #totalBox .lh-total-pricing-value--total {
    font-size: clamp(0.75rem, calc(0.35rem + 5cqi), 1.05rem);
  }

  /* Modal confirmare: același principiu (container = panoul dialog) */
  .lh-booking-confirm-panel {
    container-type: inline-size;
    container-name: lhBookConfirm;
  }
  .lh-booking-confirm-panel .lh-confirm-pricing-row {
    display: flex;
    flex-flow: row nowrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    min-width: 0;
  }
  .lh-booking-confirm-panel .lh-confirm-pricing-label {
    flex-shrink: 0;
    white-space: nowrap;
  }
  .lh-booking-confirm-panel #lh-confirm-total {
    min-width: 0;
    text-align: right;
    white-space: nowrap;
    line-height: 1.15;
    font-size: clamp(0.58rem, calc(0.28rem + 5cqi), 1rem);
  }

  .flatpickr-day .lh-cal-day-price {
    display: block;
    font-size: 0.625rem;
    font-weight: 600;
    color: rgb(100 116 139);
    margin-top: 0.1rem;
    line-height: 1.1;
    pointer-events: none;
  }
  .flatpickr-day.flatpickr-disabled .lh-cal-day-price {
    opacity: 0.4;
  }
  /* Zi validă doar ca check-out (turn-over în aceeași zi); nu e flatpickr-disabled */
  .flatpickr-day.lh-cal-checkout-only:not(.flatpickr-disabled) {
    box-shadow: inset 0 0 0 1px rgb(148 163 184 / 0.65);
  }
  .flatpickr-day.lh-cal-checkout-only:not(.flatpickr-disabled) .lh-cal-day-price {
    color: rgb(71 85 105);
  }
</style>
