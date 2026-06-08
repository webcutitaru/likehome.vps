<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BookedDatesController;
use App\Http\Controllers\Api\BookingPricePreviewController;
use App\Http\Controllers\Api\CompleteOnlineBookingController;
use App\Http\Controllers\Api\CreateBookingController;
use App\Http\Controllers\Api\MaibCallbackController;
use App\Http\Controllers\Api\PropertyFilterController;
use Illuminate\Support\Facades\Route;

Route::get('/booked-dates', [BookedDatesController::class, 'index']);

Route::middleware('web')->group(function (): void {
    Route::post('/properties/filter', [PropertyFilterController::class, 'store']);
    Route::post('/booking/price-preview', [BookingPricePreviewController::class, 'store']);
    Route::post('/booking/create', [CreateBookingController::class, 'store']);
    Route::post('/booking/complete', [CompleteOnlineBookingController::class, 'store']);
});

Route::post('/maib/callback', [MaibCallbackController::class, 'store']);
