<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Logistics\DeliveryController;
use App\Http\Controllers\Api\Logistics\DeliveryTripController;
use App\Http\Controllers\Api\Logistics\DeliveryZoneController;
use App\Http\Controllers\Api\Logistics\ReturnPickupController;
use App\Http\Controllers\Api\Logistics\UnifiedDeliveryController;
use App\Http\Controllers\Api\Logistics\VehicleController;
use App\Http\Controllers\Api\ProductCatalog\DeliveryFeeSettingController;

Route::prefix('logistics')->group(function () {
    Route::prefix('settings/delivery-fees')->group(function () {
        Route::get('/', [DeliveryFeeSettingController::class, 'show']);
        Route::put('/', [DeliveryFeeSettingController::class, 'update']);
        Route::post('/estimate', [DeliveryFeeSettingController::class, 'estimate']);
    });

    Route::prefix('delivery-orders')->group(function () {
        Route::get('/logistics-employees', [UnifiedDeliveryController::class, 'logisticsEmployees']);
        Route::post('/distance-estimate', [UnifiedDeliveryController::class, 'estimateDistance']);
        Route::post('/assign', [UnifiedDeliveryController::class, 'assign']);
        // Drivers are authorized inside the controller and are restricted to their own assignments.
        Route::get('/', [UnifiedDeliveryController::class, 'orders']);
        Route::get('/{source}/{orderId}', [UnifiedDeliveryController::class, 'orderDetail']);
        Route::get('/{source}/{orderId}/proof/{kind}', [UnifiedDeliveryController::class, 'serveProof']);
        Route::get('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'logs']);
        Route::post('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'addLog']);
        Route::put('/{source}/{orderId}/status', [UnifiedDeliveryController::class, 'updateStatus']);
        Route::post('/{source}/{orderId}/status', [UnifiedDeliveryController::class, 'updateStatus']);
        Route::post('/{source}/{orderId}/location', [UnifiedDeliveryController::class, 'updateLocation']);
        Route::post('/{source}/{orderId}/delivered', [UnifiedDeliveryController::class, 'delivered']);
    });

    // Delivery Management
    Route::prefix('deliveries')->group(function () {
        Route::get('/drivers', [DeliveryController::class, 'drivers']);
        Route::get('/', [DeliveryController::class, 'index']);
        Route::get('/{id}', [DeliveryController::class, 'show']);
        Route::put('/{id}/status', [DeliveryController::class, 'updateStatus']);
        Route::post('/{id}/assign-driver', [DeliveryController::class, 'assignDriver']);
        Route::post('/{id}/proof', [DeliveryController::class, 'uploadProof']);
        Route::get('/{id}/logs', [DeliveryController::class, 'logs']);
        Route::post('/{id}/logs', [DeliveryController::class, 'addLog']);
    });

    // Fleet Management (in-house)
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store']);
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::put('/{id}', [VehicleController::class, 'update']);
    });

    // Delivery Zones & Pricing
    Route::prefix('zones')->group(function () {
        Route::get('/', [DeliveryZoneController::class, 'index']);
        Route::post('/', [DeliveryZoneController::class, 'store']);
        Route::get('/{id}', [DeliveryZoneController::class, 'show']);
        Route::put('/{id}', [DeliveryZoneController::class, 'update']);

        Route::get('/{zoneId}/rates', [DeliveryZoneController::class, 'rates']);
        Route::post('/{zoneId}/rates', [DeliveryZoneController::class, 'addRate']);
        Route::put('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'updateRate']);
        Route::delete('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'deleteRate']);
    });

    // Delivery Trips
    Route::prefix('trips')->group(function () {
        Route::get('/', [DeliveryTripController::class, 'index']);
        Route::post('/', [DeliveryTripController::class, 'store']);
        Route::get('/{id}', [DeliveryTripController::class, 'show']);
        Route::put('/{id}/status', [DeliveryTripController::class, 'updateStatus']);
        Route::post('/{id}/orders', [DeliveryTripController::class, 'addOrders']);
        Route::post('/{id}/orders/remove', [DeliveryTripController::class, 'removeOrders']);
    });

    // Return Pickups (Customer Returns)
    Route::prefix('return-pickups')->group(function () {
        Route::get('/options/branches', [ReturnPickupController::class, 'branches']);
        Route::get('/', [ReturnPickupController::class, 'index']);
        Route::get('/{pickup}', [ReturnPickupController::class, 'show']);
        Route::put('/{pickup}', [ReturnPickupController::class, 'updateStatus']);
        Route::post('/{pickup}', [ReturnPickupController::class, 'updateStatus']);
        Route::post('/{pickup}/location', [ReturnPickupController::class, 'updateLocation']);
        Route::post('/{pickup}/assign-driver', [ReturnPickupController::class, 'assignDriver']);
        Route::post('/{pickup}/proof', [ReturnPickupController::class, 'uploadProof']);
    });
});
