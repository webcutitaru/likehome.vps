@php
    \App\Legacy\LegacyBridge::boot();
    $propertyId = (int) ($property['id'] ?? 0);
    $csrf = csrf_token();
@endphp
<script>
window.LH_BOOKING = {
  propertyId: {{ $propertyId }},
  csrf: @json($csrf),
  ajaxBookedDates: @json(url('/api/booked-dates')),
  ajaxCreateBooking: @json(url('/api/booking/create')),
  ajaxBookingPricePreview: @json(url('/api/booking/price-preview')),
  minStay: {{ max(1, (int) ($property['min_stay'] ?? 1)) }},
};
</script>
@include('legacy.property-details-booking-js')
