<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Logistics\DeliveryController;
use App\Http\Controllers\Api\Logistics\DeliveryZoneController;
use App\Http\Controllers\Api\Logistics\UnifiedDeliveryController;
use App\Http\Controllers\Api\Logistics\VehicleController;

Route::prefix('logistics')->group(function () {
    Route::prefix('delivery-orders')->group(function () {
        Route::get('/logistics-employees', [UnifiedDeliveryController::class, 'logisticsEmployees'])->middleware('can:logistics.deliveries.view');
        Route::post('/distance-estimate', [UnifiedDeliveryController::class, 'estimateDistance'])->middleware('can:logistics.deliveries.manage');
        Route::post('/assign', [UnifiedDeliveryController::class, 'assign'])->middleware('can:logistics.deliveries.manage');
        Route::get('/', [UnifiedDeliveryController::class, 'orders'])->middleware('can:logistics.deliveries.view');
        Route::get('/{source}/{orderId}', [UnifiedDeliveryController::class, 'orderDetail'])->middleware('can:logistics.deliveries.view');
        Route::get('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'logs'])->middleware('can:logistics.deliveries.view');
        Route::post('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'addLog'])->middleware('can:logistics.deliveries.manage');
        Route::put('/{source}/{orderId}/status', [UnifiedDeliveryController::class, 'updateStatus'])->middleware('can:logistics.deliveries.manage');
        Route::post('/{source}/{orderId}/delivered', [UnifiedDeliveryController::class, 'delivered'])->middleware('can:logistics.deliveries.manage');
    });

    // Delivery Management
    Route::prefix('deliveries')->group(function () {
        Route::get('/drivers', [DeliveryController::class, 'drivers'])->middleware('can:logistics.deliveries.view');
        Route::get('/', [DeliveryController::class, 'index'])->middleware('can:logistics.deliveries.view');
        Route::get('/{id}', [DeliveryController::class, 'show'])->middleware('can:logistics.deliveries.view');
        Route::put('/{id}/status', [DeliveryController::class, 'updateStatus'])->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/assign-driver', [DeliveryController::class, 'assignDriver'])->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/proof', [DeliveryController::class, 'uploadProof'])->middleware('can:logistics.deliveries.manage');
        Route::get('/{id}/logs', [DeliveryController::class, 'logs'])->middleware('can:logistics.deliveries.view');
        Route::post('/{id}/logs', [DeliveryController::class, 'addLog'])->middleware('can:logistics.deliveries.manage');
    });

    // Fleet Management (in-house)
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->middleware('can:logistics.fleet.view');
        Route::post('/', [VehicleController::class, 'store'])->middleware('can:logistics.fleet.manage');
        Route::get('/{id}', [VehicleController::class, 'show'])->middleware('can:logistics.fleet.view');
        Route::put('/{id}', [VehicleController::class, 'update'])->middleware('can:logistics.fleet.manage');
    });

    // Delivery Zones & Pricing
    Route::prefix('zones')->group(function () {
        Route::get('/', [DeliveryZoneController::class, 'index'])->middleware('can:logistics.zones.view');
        Route::post('/', [DeliveryZoneController::class, 'store'])->middleware('can:logistics.zones.manage');
        Route::get('/{id}', [DeliveryZoneController::class, 'show'])->middleware('can:logistics.zones.view');
        Route::put('/{id}', [DeliveryZoneController::class, 'update'])->middleware('can:logistics.zones.manage');

        Route::get('/{zoneId}/rates', [DeliveryZoneController::class, 'rates'])->middleware('can:logistics.zones.view');
        Route::post('/{zoneId}/rates', [DeliveryZoneController::class, 'addRate'])->middleware('can:logistics.zones.manage');
        Route::put('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'updateRate'])->middleware('can:logistics.zones.manage');
        Route::delete('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'deleteRate'])->middleware('can:logistics.zones.manage');
    });
});
