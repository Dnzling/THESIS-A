<?php

use App\Http\Controllers\Api\WarehouseOperations\WarehouseOperationsController;
use App\Http\Controllers\Api\WarehouseOperations\WarehousePurchaseRequisitionController;
use Illuminate\Support\Facades\Route;

Route::prefix('warehouse')->controller(WarehouseOperationsController::class)->group(function () {
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
    Route::post('transfer-requests/{id}/reject', 'rejectTransferRequest')->whereNumber('id');
    Route::get('receiving', 'receiving');
    Route::get('transfer-history', 'transferHistory');
});

Route::prefix('warehouse/purchase-requisitions')->controller(WarehousePurchaseRequisitionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('options', 'options');
    Route::post('/', 'store');
});
