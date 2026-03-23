<?php

use App\Http\Controllers\Api\Sales\SalesCrmController;
use App\Http\Controllers\Api\Sales\SalesPosController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->group(function () {
    Route::get('/dashboard', [SalesPosController::class, 'dashboard']);

    Route::prefix('crm')->group(function () {
        Route::get('/leads', [SalesCrmController::class, 'leads']);
        Route::post('/leads', [SalesCrmController::class, 'storeLead']);
        Route::put('/leads/{id}', [SalesCrmController::class, 'updateLead']);
        Route::post('/leads/{id}/stage', [SalesCrmController::class, 'changeStage']);
        Route::get('/leads/{id}/activities', [SalesCrmController::class, 'activities']);
        Route::post('/leads/{id}/activities', [SalesCrmController::class, 'addActivity']);
    });

    Route::prefix('pos')->group(function () {
        Route::get('/products', [SalesPosController::class, 'products']);
        Route::post('/checkout', [SalesPosController::class, 'checkout']);
        Route::get('/orders', [SalesPosController::class, 'orders']);
        Route::get('/orders/{id}', [SalesPosController::class, 'show']);
    });
});

