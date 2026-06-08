<?php

use App\Http\Controllers\Admin\BookingActionController;
use App\Http\Controllers\Admin\CalendarActionController;
use App\Http\Controllers\Web\BookingPaymentController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PropertyController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    Route::post('calendar-action', CalendarActionController::class)->name('admin.calendar.action');
    Route::post('booking-action', BookingActionController::class)->name('admin.booking.action');
});

Route::redirect('properties.php', '/proprietati', 301);
Route::redirect('index.php', '/', 301);
Route::redirect('about.php', '/despre-noi', 301);
Route::redirect('contact.php', '/contact', 301);
Route::redirect('faq.php', '/intrebari-frecvente', 301);
Route::redirect('privacy.php', '/confidentialitate', 301);
Route::redirect('terms.php', '/termeni', 301);
Route::redirect('booking-payment-success.php', '/rezervare/succes', 301);
Route::redirect('booking-payment-failed.php', '/rezervare/esuat', 301);
Route::redirect('sitemap.php', '/sitemap.xml', 301);

Route::middleware('legacy.redirects')->group(function (): void {
    Route::get('property-details.php', static fn () => abort(404));
    Route::get('en/property-details.php', static fn () => abort(404));
    Route::get('ru/property-details.php', static fn () => abort(404));
});

foreach (['en', 'ru'] as $prefix) {
    Route::redirect("{$prefix}/index.php", "/{$prefix}", 301);
    Route::redirect("{$prefix}/properties.php", "/{$prefix}/properties", 301);
    Route::redirect("{$prefix}/about.php", "/{$prefix}/about", 301);
    Route::redirect("{$prefix}/contact.php", "/{$prefix}/contact", 301);
    Route::redirect("{$prefix}/faq.php", "/{$prefix}/faq", 301);
    Route::redirect("{$prefix}/privacy.php", "/{$prefix}/privacy", 301);
    Route::redirect("{$prefix}/terms.php", "/{$prefix}/terms", 301);
    Route::redirect("{$prefix}/booking-payment-success.php", "/{$prefix}/booking/success", 301);
    Route::redirect("{$prefix}/booking-payment-failed.php", "/{$prefix}/booking/failed", 301);
}

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

$registerLocale = static function (string $locale, array $paths): void {
    $callback = static function () use ($locale, $paths): void {
        Route::get($paths['home'], [HomeController::class, 'index'])->name("{$locale}.home");
        Route::get($paths['properties'], [PropertyController::class, 'index'])->name("{$locale}.properties.index");
        Route::get($paths['properties'] . '/{slug}', [PropertyController::class, 'show'])->name("{$locale}.properties.show");
        Route::get($paths['about'], [StaticPageController::class, 'about'])->name("{$locale}.about");
        Route::get($paths['contact'], [StaticPageController::class, 'contact'])->name("{$locale}.contact");
        Route::get($paths['faq'], [StaticPageController::class, 'faq'])->name("{$locale}.faq");
        Route::get($paths['privacy'], [StaticPageController::class, 'privacy'])->name("{$locale}.privacy");
        Route::get($paths['terms'], [StaticPageController::class, 'terms'])->name("{$locale}.terms");
        Route::get($paths['booking_success'], [BookingPaymentController::class, 'success'])->name("{$locale}.booking.success");
        Route::get($paths['booking_failed'], [BookingPaymentController::class, 'failed'])->name("{$locale}.booking.failed");
    };

    if ($locale === 'ro') {
        Route::middleware('locale')->group($callback);
    } else {
        Route::prefix($locale)->middleware('locale')->group($callback);
    }
};

$registerLocale('ro', [
    'home' => '/',
    'properties' => 'proprietati',
    'about' => 'despre-noi',
    'contact' => 'contact',
    'faq' => 'intrebari-frecvente',
    'privacy' => 'confidentialitate',
    'terms' => 'termeni',
    'booking_success' => 'rezervare/succes',
    'booking_failed' => 'rezervare/esuat',
]);

$registerLocale('en', [
    'home' => '/',
    'properties' => 'properties',
    'about' => 'about',
    'contact' => 'contact',
    'faq' => 'faq',
    'privacy' => 'privacy',
    'terms' => 'terms',
    'booking_success' => 'booking/success',
    'booking_failed' => 'booking/failed',
]);

$registerLocale('ru', [
    'home' => '/',
    'properties' => 'properties',
    'about' => 'about',
    'contact' => 'contact',
    'faq' => 'faq',
    'privacy' => 'privacy',
    'terms' => 'terms',
    'booking_success' => 'booking/success',
    'booking_failed' => 'booking/failed',
]);
