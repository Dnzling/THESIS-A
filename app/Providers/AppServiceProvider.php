<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Inventory Services in Service Container
        $this->app->bind(\App\Services\Inventory\InventoryService::class, function ($app) {
            return new \App\Services\Inventory\InventoryService();
        });

        $this->app->bind(\App\Services\Inventory\ApprovalService::class, function ($app) {
            return new \App\Services\Inventory\ApprovalService();
        });

        $this->app->bind(\App\Services\Inventory\StockTransferService::class, function ($app) {
            return new \App\Services\Inventory\StockTransferService(
                $app->make(\App\Services\Inventory\InventoryService::class),
                $app->make(\App\Services\Inventory\ApprovalService::class)
            );
        });

        $this->app->bind(\App\Services\Inventory\AlertService::class, function ($app) {
            return new \App\Services\Inventory\AlertService();
        });

        $this->app->bind(\App\Services\Inventory\ReportingService::class, function ($app) {
            return new \App\Services\Inventory\ReportingService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiters must be registered outside routes when route caching is enabled.
        RateLimiter::for('login', fn(Request $request) => Limit::perMinute(100)->by($request->ip()));
        RateLimiter::for('login-with-clock-in', fn(Request $request) => Limit::perMinute(100)->by($request->ip()));
        RateLimiter::for('api', fn(Request $request) => Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('register', fn(Request $request) => Limit::perHour(100)->by($request->ip()));
        RateLimiter::for('password-reset', fn(Request $request) => Limit::perHour(5)->by($request->ip()));

        Vite::prefetch(concurrency: 3);

        // Register Inventory Observers
        \App\Models\ProductCatalog\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\Inventory\BranchInventory::observe(\App\Observers\BranchInventoryObserver::class);

        // TEMP: Bypass permission checks globally (until RBAC is re-enabled)
        Gate::before(function ($user, string $ability) {
            return true;
        });
    }
}
