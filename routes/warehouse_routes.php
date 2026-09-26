<?php

use App\Http\Controllers\Api\WarehouseOperations\WarehouseOperationsController;
use App\Http\Controllers\Api\WarehouseOperations\WarehousePurchaseRequisitionController;
use App\Http\Controllers\Api\WarehouseOperations\WarehouseReorderController;
use App\Http\Controllers\Api\Inventory\ForecastController;
use App\Http\Controllers\Api\Logistics\ReturnPickupController;
use Illuminate\Support\Facades\Route;

Route::prefix('warehouse')->controller(WarehouseOperationsController::class)->group(function () {
    Route::get('returns', [ReturnPickupController::class, 'warehouseReturns']);
    Route::get('returns/{pickup}', [ReturnPickupController::class, 'warehouseShow'])->whereNumber('pickup');
    Route::post('returns/{return}/inspect', [\App\Http\Controllers\Api\CRM\ReturnController::class, 'receive']);
    Route::get('dashboard', 'dashboard');
    Route::get('warehouses', 'warehouses');
    Route::get('warehouses/{id}', 'warehouse')->whereNumber('id');
    Route::get('stock', 'stock');
    Route::get('stock-options', 'stockItemOptions');
    Route::get('stock/{id}', 'stockItem')->whereNumber('id');
    Route::put('stock/{id}', 'updateStockItem')->whereNumber('id');
    Route::get('transfer-requests', 'transferRequests');
    Route::get('transfer-requests/{id}', 'transferRequest')->whereNumber('id');
    Route::post('transfer-requests/{id}/approve', 'approveTransferRequest')->whereNumber('id');
    Route::post('transfer-requests/{id}/ready-for-dispatch', 'readyTransferForDispatch')->whereNumber('id');
    Route::post('transfer-requests/{id}/reject', 'rejectTransferRequest')->whereNumber('id');
    Route::get('receiving', 'receiving');
    Route::get('transfer-history', 'transferHistory');
});

Route::prefix('warehouse/purchase-requisitions')->controller(WarehousePurchaseRequisitionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('options', 'options');
    Route::get('{id}', 'show')->whereNumber('id');
    Route::post('/', 'store');
});

Route::prefix('warehouse/reorder')->controller(WarehouseReorderController::class)->group(function () {
    Route::get('rules', 'rules');
    Route::get('options', 'options');
    Route::post('rules', 'saveRule');
    Route::get('suggestions', 'suggestions');
    Route::post('suggestions/generate', 'generate');
    Route::post('suggestions/{suggestion}/approve', 'approve')->whereNumber('suggestion');
});
Route::get('warehouse/forecasting', fn (\Illuminate\Http\Request $request, ForecastController $controller) => $controller->index($request, 'warehouse'));
