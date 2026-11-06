<?php

use App\Http\Controllers\Api\ProductCatalog\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('merchandising')->group(function (): void {
    Route::get('/reports', [DashboardController::class, 'reports']);
});
