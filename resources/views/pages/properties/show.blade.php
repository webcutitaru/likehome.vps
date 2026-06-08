@extends('layouts.app')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
@include('legacy.property-details-styles')
@endpush

@section('content')
@php \App\Legacy\LegacyBridge::boot(); @endphp
@include('legacy.property-details-body')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
@include('legacy.property-details-scripts')
@endpush
