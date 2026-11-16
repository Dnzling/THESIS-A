<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Logistics\DeliveryController;
use App\Http\Controllers\Api\Logistics\DeliveryTripController;
use App\Http\Controllers\Api\Logistics\DeliveryZoneController;
use App\Http\Controllers\Api\Logistics\ReturnPickupController;
use App\Http\Controllers\Api\Logistics\ReplacementController;
use App\Http\Controllers\Api\Logistics\UnifiedDeliveryController;
use App\Http\Controllers\Api\Logistics\LalamoveDeliveryController;
use App\Http\Controllers\Api\Logistics\VehicleController;
use App\Http\Controllers\Api\Logistics\SupplierPickupAssignmentController;
use App\Http\Controllers\Api\Inventory\StockTransferController;
use App\Http\Controllers\Api\ProductCatalog\DeliveryFeeSettingController;

Route::prefix('logistics')->group(function () {
    Route::prefix('supplier-pickups/{id}')->whereNumber('id')->middleware(
        'permission.any:logistics.supplier-pickups.view,logistics.supplier-pickups.manage,logistics.driver.supplier-pickups'
    )->group(function () {
        Route::get('/assignment', [SupplierPickupAssignmentController::class, 'showAssignment']);
        Route::get('/vehicles', [SupplierPickupAssignmentController::class, 'pickupVehicles']);
        Route::get('/drivers', [SupplierPickupAssignmentController::class, 'pickupDrivers']);
        Route::post('/assign', [SupplierPickupAssignmentController::class, 'assignPickup'])->middleware('can:logistics.supplier-pickups.manage');
    });
    Route::prefix('replacements')->group(function () {
        Route::get('/', [ReplacementController::class, 'index'])->middleware('can:logistics.return-pickups.view');
        Route::get('/{id}', [ReplacementController::class, 'show'])->middleware('can:logistics.return-pickups.view');
        Route::post('/{id}/sales-request', [ReplacementController::class, 'requestSalesAlternative'])->middleware('can:logistics.return-pickups.manage');
        Route::post('/{id}/reserve', [ReplacementController::class, 'reserve'])->middleware('can:logistics.return-pickups.manage');
        Route::post('/{id}/alternative', [ReplacementController::class, 'reserveAlternative'])->middleware('can:logistics.return-pickups.manage');
        Route::post('/{id}/deliver', [ReplacementController::class, 'deliver'])->middleware('permission.any:logistics.return-pickups.manage,logistics.deliveries.manage,logistics.driver.trips');
        Route::post('/{id}/fail', [ReplacementController::class, 'fail'])->middleware('permission.any:logistics.return-pickups.manage,logistics.deliveries.manage,logistics.driver.trips');
        Route::post('/{id}/reattempt', [ReplacementController::class, 'reattempt'])->middleware('can:logistics.return-pickups.manage');
        Route::post('/{id}/disposition', [ReplacementController::class, 'disposition'])->middleware('can:logistics.return-pickups.manage');
    });
    Route::prefix('settings/delivery-fees')->middleware('can:logistics.shipping_fee.view')->group(function () {
        Route::get('/', [DeliveryFeeSettingController::class, 'show']);
        Route::put('/', [DeliveryFeeSettingController::class, 'update'])->middleware('can:logistics.shipping_fee.manage');
        Route::post('/estimate', [DeliveryFeeSettingController::class, 'estimate']);
    });

    // Logistics-facing stock transfer workflow. Inventory screens keep using inventory_routes.
    Route::prefix('stock-transfers')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->middleware('can:logistics.deliveries.view');
        Route::get('/{id}', [StockTransferController::class, 'show'])->whereNumber('id')->middleware('can:logistics.deliveries.view');
        Route::post('/{id}/create-delivery', [StockTransferController::class, 'createDelivery'])->whereNumber('id')->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/delivery-log', [StockTransferController::class, 'addDeliveryLog'])->whereNumber('id')->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/receive', [StockTransferController::class, 'receive'])->whereNumber('id')->middleware('can:logistics.deliveries.manage');
    });

    Route::prefix('delivery-orders')->group(function () {
        Route::get('/lalamove/services', [LalamoveDeliveryController::class, 'services'])->middleware('can:logistics.deliveries.view');
        Route::post('/{source}/{orderId}/lalamove/quote', [LalamoveDeliveryController::class, 'quote'])->whereNumber('orderId')->middleware('can:logistics.deliveries.manage');
        Route::post('/{source}/{orderId}/lalamove/book', [LalamoveDeliveryController::class, 'book'])->whereNumber('orderId')->middleware('can:logistics.deliveries.manage');
        Route::post('/{source}/{orderId}/lalamove/refresh', [LalamoveDeliveryController::class, 'refresh'])->whereNumber('orderId')->middleware('can:logistics.deliveries.manage');
        Route::get('/logistics-employees', [UnifiedDeliveryController::class, 'logisticsEmployees'])->middleware('can:logistics.deliveries.view');
        Route::post('/distance-estimate', [UnifiedDeliveryController::class, 'estimateDistance'])->middleware('can:logistics.deliveries.view');
        Route::post('/assign', [UnifiedDeliveryController::class, 'assignmentMovedToTrips'])->middleware('can:logistics.deliveries.manage');
        // Drivers are authorized inside the controller and are restricted to their own assignments.
        Route::middleware('permission.any:logistics.deliveries.view,logistics.deliveries.manage,logistics.driver.history,logistics.driver.supplier-pickups,logistics.driver.trips')->group(function () {
            Route::get('/', [UnifiedDeliveryController::class, 'orders']);
            Route::get('/{source}/{orderId}', [UnifiedDeliveryController::class, 'orderDetail']);
            Route::get('/{source}/{orderId}/proof/{kind}', [UnifiedDeliveryController::class, 'serveProof']);
            Route::get('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'logs']);
        });
        Route::middleware('permission.any:logistics.deliveries.manage,logistics.driver.supplier-pickups,logistics.driver.trips')->group(function () {
            Route::post('/{source}/{orderId}/logs', [UnifiedDeliveryController::class, 'addLog']);
            Route::put('/{source}/{orderId}/status', [UnifiedDeliveryController::class, 'updateStatus']);
            Route::post('/{source}/{orderId}/status', [UnifiedDeliveryController::class, 'updateStatus']);
            Route::post('/{source}/{orderId}/location', [UnifiedDeliveryController::class, 'updateLocation']);
            Route::post('/{source}/{orderId}/delivered', [UnifiedDeliveryController::class, 'delivered']);
        });
    });

    // Delivery Management
    Route::prefix('deliveries')->middleware('can:logistics.deliveries.view')->group(function () {
        Route::get('/drivers', [DeliveryController::class, 'drivers']);
        Route::get('/', [DeliveryController::class, 'index']);
        Route::get('/{id}', [DeliveryController::class, 'show']);
        Route::put('/{id}/status', [DeliveryController::class, 'updateStatus'])->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/assign-driver', [DeliveryController::class, 'assignDriver'])->middleware('can:logistics.deliveries.manage');
        Route::post('/{id}/proof', [DeliveryController::class, 'uploadProof'])->middleware('can:logistics.deliveries.manage');
        Route::get('/{id}/logs', [DeliveryController::class, 'logs']);
        Route::post('/{id}/logs', [DeliveryController::class, 'addLog'])->middleware('can:logistics.deliveries.manage');
    });

    // Fleet Management (in-house)
    Route::prefix('vehicles')->middleware('can:logistics.fleet.view')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store'])->middleware('can:logistics.fleet.manage');
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::put('/{id}', [VehicleController::class, 'update'])->middleware('can:logistics.fleet.manage');
    });

    // Delivery Zones & Pricing
    Route::prefix('zones')->middleware('can:logistics.shipping_fee.view')->group(function () {
        Route::get('/', [DeliveryZoneController::class, 'index']);
        Route::post('/', [DeliveryZoneController::class, 'store'])->middleware('can:logistics.shipping_fee.manage');
        Route::get('/{id}', [DeliveryZoneController::class, 'show']);
        Route::put('/{id}', [DeliveryZoneController::class, 'update'])->middleware('can:logistics.shipping_fee.manage');

        Route::get('/{zoneId}/rates', [DeliveryZoneController::class, 'rates']);
        Route::post('/{zoneId}/rates', [DeliveryZoneController::class, 'addRate'])->middleware('can:logistics.shipping_fee.manage');
        Route::put('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'updateRate'])->middleware('can:logistics.shipping_fee.manage');
        Route::delete('/{zoneId}/rates/{rateId}', [DeliveryZoneController::class, 'deleteRate'])->middleware('can:logistics.shipping_fee.manage');
    });

    // Delivery Trips
    Route::prefix('trips')->group(function () {
        Route::get('/', [DeliveryTripController::class, 'index'])->middleware('permission.any:logistics.trip.view,logistics.trip.manage,logistics.deliveries.view,logistics.deliveries.manage,logistics.driver.trips');
        Route::get('/compatible', [DeliveryTripController::class, 'compatibleForOrder'])->middleware('can:logistics.trip.view');
        Route::get('/vehicle-unavailable-dates', [DeliveryTripController::class, 'unavailableDepartureDates'])->middleware('can:logistics.trip.view');
        Route::post('/', [DeliveryTripController::class, 'store'])->middleware('can:logistics.trip.manage');
        Route::get('/{id}/suggestions', [DeliveryTripController::class, 'suggestions'])->middleware('can:logistics.trip.view');
        Route::get('/{id}/print', [DeliveryTripController::class, 'printManifest'])->middleware('permission.any:logistics.trip.view,logistics.trip.manage,logistics.deliveries.view,logistics.deliveries.manage,logistics.driver.trips');
        Route::get('/{id}', [DeliveryTripController::class, 'show'])->middleware('permission.any:logistics.trip.view,logistics.trip.manage,logistics.deliveries.view,logistics.deliveries.manage,logistics.driver.trips');
        Route::put('/{id}', [DeliveryTripController::class, 'update'])->middleware('can:logistics.trip.manage');
        Route::put('/{id}/status', [DeliveryTripController::class, 'updateStatus'])->middleware('permission.any:logistics.trip.manage,logistics.deliveries.manage,logistics.driver.trips');
        Route::post('/{id}/orders', [DeliveryTripController::class, 'addOrders'])->middleware('can:logistics.trip.manage');
        Route::post('/{id}/orders/remove', [DeliveryTripController::class, 'removeOrders'])->middleware('can:logistics.trip.manage');
    });

    // Return Pickups (Customer Returns)
    Route::prefix('return-pickups')->middleware('permission.any:logistics.return-pickups.view,logistics.return-pickups.manage,logistics.driver.trips')->group(function () {
        Route::get('/options/branches', [ReturnPickupController::class, 'branches'])->middleware('permission.any:logistics.return-pickups.view,logistics.return-pickups.manage');
        Route::post('/{pickup}/estimate', [ReturnPickupController::class, 'estimate'])->middleware('permission.any:logistics.return-pickups.view,logistics.return-pickups.manage');
        Route::get('/', [ReturnPickupController::class, 'index'])->middleware('permission.any:logistics.return-pickups.view,logistics.return-pickups.manage');
        Route::get('/{pickup}', [ReturnPickupController::class, 'show']);
        Route::put('/{pickup}', [ReturnPickupController::class, 'updateStatus'])->middleware('permission.any:logistics.return-pickups.manage,logistics.driver.trips');
        Route::post('/{pickup}', [ReturnPickupController::class, 'updateStatus'])->middleware('permission.any:logistics.return-pickups.manage,logistics.driver.trips');
        Route::post('/{pickup}/location', [ReturnPickupController::class, 'updateLocation'])->middleware('permission.any:logistics.return-pickups.manage,logistics.driver.trips');
        Route::post('/{pickup}/assign-driver', [ReturnPickupController::class, 'assignDriver'])->middleware('permission.any:logistics.return-pickups.manage');
        Route::post('/{pickup}/proof', [ReturnPickupController::class, 'uploadProof'])->middleware('can:logistics.return-pickups.manage');
    });
});
