<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);
    }
}
