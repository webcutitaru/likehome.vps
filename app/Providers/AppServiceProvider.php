<?php

namespace App\Providers;

use App\Livewire\SubdirectoryHandleRequests;
use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\DiscountCoupon;
use App\Models\Property;
use App\Models\User;
use App\Policies\AdminActivityLogPolicy;
use App\Policies\BookingPolicy;
use App\Policies\DiscountCouponPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HandleRequests::class, SubdirectoryHandleRequests::class);
    }

    public function boot(): void
    {
        if (! app()->runningUnitTests() && config('database.default') === 'mysql') {
            DB::prohibitDestructiveCommands();
        }

        if ($root = config('app.url')) {
            URL::forceRootUrl(rtrim($root, '/'));
        }

        $this->configureSubdirectoryUrls();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(DiscountCoupon::class, DiscountCouponPolicy::class);
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(AdminActivityLog::class, AdminActivityLogPolicy::class);
    }

    private function configureSubdirectoryUrls(): void
    {
        $basePath = parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_PATH) ?: '';
        $basePath = ($basePath === '' || $basePath === '/') ? '' : rtrim($basePath, '/');

        if ($basePath === '') {
            return;
        }

        config(['livewire.asset_url' => $basePath.'/livewire/livewire.js']);
    }
}
