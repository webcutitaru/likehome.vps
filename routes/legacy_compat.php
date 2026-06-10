<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BookedDatesController;
use App\Http\Controllers\Api\BookingPricePreviewController;
use App\Http\Controllers\Api\CompleteOnlineBookingController;
use App\Http\Controllers\Api\CreateBookingController;
use App\Http\Controllers\Api\MaibCallbackController;
use App\Http\Controllers\Api\PropertyFilterController;
use App\Http\Controllers\Legacy\LegacyCronController;
use App\Http\Controllers\Legacy\MaibCheckController;
use App\Http\Controllers\Legacy\RobotsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', RobotsController::class);
Route::get('robots.php', RobotsController::class);

Route::get('maib_check.php', MaibCheckController::class);

Route::get('ical/export.php', static function (Request $request) {
    $token = trim((string) $request->query('token', ''));
    if ($token === '') {
        abort(404);
    }

    return redirect('/ical/'.$token.'.ics', 301);
});

Route::get('cron/ical_sync.php', [LegacyCronController::class, 'icalSync']);
Route::get('cron/checkin_reminder.php', [LegacyCronController::class, 'checkinReminder']);
Route::get('cron/expire_pending_bookings.php', [LegacyCronController::class, 'expirePending']);

Route::get('ajax/get_booked_dates.php', [BookedDatesController::class, 'index']);

Route::middleware('web')->group(function (): void {
    Route::post('ajax/filter_properties.php', [PropertyFilterController::class, 'store']);
    Route::post('ajax/booking_price_preview.php', [BookingPricePreviewController::class, 'store']);
    Route::post('ajax/create_booking.php', [CreateBookingController::class, 'store']);
    Route::post('ajax/complete_online_booking.php', [CompleteOnlineBookingController::class, 'store']);
});

Route::post('ajax/maib_callback.php', [MaibCallbackController::class, 'store']);

foreach ([
    'login.php' => '/admin/login',
    'index.php' => '/admin',
    'dashboard.php' => '/admin',
    'calendar.php' => '/admin/property-calendar',
    'bookings.php' => '/admin/bookings',
    'add-property.php' => '/admin/properties/create',
    'coupons.php' => '/admin/discount-coupons',
    'users.php' => '/admin/users',
    'activity-log.php' => '/admin/admin-activity-logs',
] as $script => $target) {
    Route::redirect('admin/'.$script, $target, 301);
}

Route::get('admin/edit-property.php', static function (Request $request) {
    $id = (int) $request->query('id', 0);

    return $id > 0
        ? redirect('/admin/edit-property/'.$id, 301)
        : redirect('/admin', 301);
});
