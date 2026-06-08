@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-14 md:py-20">
  <div class="bg-white border border-black/10 rounded-2xl p-8 sm:p-10 shadow-sm">
    <div class="flex items-start gap-4 mb-6">
      <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      </span>
      <div>
        <h1 class="text-2xl font-black text-ink tracking-tight">{{ __('payment.success.heading') }}</h1>
        <p class="text-sm text-blue-grey mt-2" id="lh-payment-success-subtitle">
          @if($booking && ($booking['payment_status'] ?? '') === 'paid')
            {{ __('payment.success.subtitle_confirmed') }}
          @else
            {{ __('payment.success.subtitle_pending') }}
          @endif
        </p>
      </div>
    </div>

    <dl id="lh-payment-success-details" class="space-y-3 text-sm mb-8 {{ $booking ? '' : 'hidden' }}">
      @if($booking)
        <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
          <dt class="text-blue-grey">{{ __('booking.confirm_property') }}</dt>
          <dd class="font-semibold text-ink text-right">{{ $booking['property_title'] ?? '' }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
          <dt class="text-blue-grey">{{ __('booking.confirm_period') }}</dt>
          <dd class="font-medium text-ink">{{ ($booking['check_in'] ?? '') }} → {{ ($booking['check_out'] ?? '') }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
          <dt class="text-blue-grey">{{ __('payment.success.paid_amount') }}</dt>
          <dd class="font-bold text-cta tabular-nums" id="lh-payment-paid-amount">
            {{ lh_format_money((float) ($booking['payment_amount'] ?? $booking['payment_due_amount'] ?? 0), 2) }}
          </dd>
        </div>
        @if(($booking['payment_status'] ?? '') === 'paid' || (float) ($booking['payment_amount'] ?? 0) > 0.004)
          <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
            <dt class="text-blue-grey">{{ __('payment.success.merchant') }}</dt>
            <dd class="font-medium text-ink text-right">{{ lh_company_legal_name() }}</dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
            <dt class="text-blue-grey">{{ __('payment.success.website') }}</dt>
            <dd class="font-medium text-ink text-right break-all">{{ $siteOrigin }}</dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
            <dt class="text-blue-grey">{{ __('payment.success.currency') }}</dt>
            <dd class="font-medium text-ink">{{ lh_company_currency() }}</dd>
          </div>
          @if($paidAtDisplay !== '')
            <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
              <dt class="text-blue-grey">{{ __('payment.success.paid_at') }}</dt>
              <dd class="font-medium text-ink tabular-nums">{{ $paidAtDisplay }}</dd>
            </div>
          @endif
        @endif
        <div class="flex justify-between gap-4 border-b border-black/6 pb-2">
          <dt class="text-blue-grey">{{ __('payment.success.order_no') }}</dt>
          <dd class="font-medium text-ink">LH-{{ (int) $booking['id'] }}</dd>
        </div>
        @if($checkoutId !== '')
          <div class="flex justify-between gap-4 pb-2">
            <dt class="text-blue-grey">checkout_id</dt>
            <dd class="font-mono text-xs text-ink break-all">{{ $checkoutId }}</dd>
          </div>
        @endif
      @endif
    </dl>

    <div class="flex flex-col sm:flex-row gap-3">
      <a href="{{ \App\Support\WebUrls::propertiesIndex() }}" class="inline-flex justify-center items-center px-5 py-3 rounded-xl font-bold bg-cta text-white hover:brightness-110 transition-all">{{ __('payment.success.back_properties') }}</a>
      <a href="{{ \App\Support\WebUrls::page('contact') }}" class="inline-flex justify-center items-center px-5 py-3 rounded-xl font-bold border-2 border-black/10 text-ink hover:bg-brand-50 transition-colors">{{ __('payment.success.contact') }}</a>
    </div>
  </div>
</div>

@if($needsPolling)
<script>
(function () {
  var checkoutId = @json($checkoutId);
  var orderId = @json($orderId);
  var completeUrl = @json($completeUrl);
  var csrf = @json($csrfToken);
  var confirmedMsg = @json(__('payment.success.subtitle_confirmed'));
  var pendingMsg = @json(__('payment.success.subtitle_pending'));

  function poll(attempt) {
    var body = new URLSearchParams();
    body.set('csrf_token', csrf);
    if (checkoutId) body.set('checkout_id', checkoutId);
    if (orderId) body.set('order_id', orderId);
    fetch(completeUrl, { method: 'POST', headers: { Accept: 'application/json' }, body: body })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var sub = document.getElementById('lh-payment-success-subtitle');
        if (data.success && data.confirmed) {
          if (sub) sub.textContent = confirmedMsg;
          return;
        }
        if (attempt < 8) {
          setTimeout(function () { poll(attempt + 1); }, 1500);
        } else if (sub) {
          sub.textContent = pendingMsg;
        }
      })
      .catch(function () {
        if (attempt < 8) setTimeout(function () { poll(attempt + 1); }, 2000);
      });
  }
  poll(0);
})();
</script>
@endif
@endsection
