<?php

use App\Http\Controllers\Api\Sales\SalesPosController;
use App\Http\Controllers\Api\Sales\SalesOrderDeliveryController;
use App\Http\Controllers\Api\Sales\SalesRefundController;
use App\Http\Controllers\Api\Sales\SalesReportsController;
use App\Http\Controllers\Api\Inventory\EcommerceOrderManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->group(function () {
    Route::get('/dashboard', [SalesPosController::class, 'dashboard'])->middleware('can:sales.dashboard.view');
    Route::get('/orders', [SalesPosController::class, 'unifiedOrders']);

    Route::prefix('pos')->group(function () {
        Route::get('/products', [SalesPosController::class, 'products'])->middleware('can:sales.pos.view');
        Route::post('/shipping-estimate', [SalesPosController::class, 'shippingEstimate'])->middleware('can:sales.pos.view');
        Route::get('/payment-options', [SalesPosController::class, 'paymentOptions'])->middleware('can:sales.pos.view');
        Route::post('/checkout', [SalesPosController::class, 'checkout'])->middleware('can:sales.pos.manage');
        Route::get('/orders', [SalesPosController::class, 'orders'])->middleware('can:sales.pos.view');
        Route::get('/orders/{id}', [SalesPosController::class, 'show'])->middleware('can:sales.pos.view');
        Route::get('/orders/{id}/receipt', [SalesPosController::class, 'receiptPdf'])->middleware('can:sales.pos.view');
        Route::post('/orders/{id}/sync-payment', [SalesPosController::class, 'syncPayment'])->middleware('can:sales.pos.manage');
        Route::post('/orders/{id}/send-to-logistics', [SalesPosController::class, 'sendToLogistics'])->middleware('can:sales.orders.manage');
    });

    Route::prefix('order-deliveries')->group(function () {
        Route::get('/drivers', [SalesOrderDeliveryController::class, 'drivers'])->middleware('can:sales.deliveries.view');
        Route::get('/', [SalesOrderDeliveryController::class, 'index'])->middleware('can:sales.deliveries.view');
        Route::get('/{id}', [SalesOrderDeliveryController::class, 'show'])->middleware('can:sales.deliveries.view');
        Route::put('/{id}/status', [SalesOrderDeliveryController::class, 'updateStatus'])->middleware('can:sales.deliveries.manage');
        Route::post('/{id}/assign-driver', [SalesOrderDeliveryController::class, 'assignDriver'])->middleware('can:sales.deliveries.manage');
        Route::post('/{id}/proof', [SalesOrderDeliveryController::class, 'uploadProof'])->middleware('can:sales.deliveries.manage');
        Route::get('/{id}/logs', [SalesOrderDeliveryController::class, 'logs'])->middleware('can:sales.deliveries.view');
        Route::post('/{id}/logs', [SalesOrderDeliveryController::class, 'addLog'])->middleware('can:sales.deliveries.manage');
    });

    Route::prefix('ecommerce-orders')->group(function () {
        Route::get('/', [EcommerceOrderManagementController::class, 'index'])->middleware('can:sales.orders.view');
        Route::get('/{id}', [EcommerceOrderManagementController::class, 'show'])->middleware('can:sales.orders.view');
        Route::get('/{id}/receipt', [EcommerceOrderManagementController::class, 'receiptPdf'])->middleware('can:sales.orders.view');
        Route::put('/{id}/status', [EcommerceOrderManagementController::class, 'updateStatus'])->middleware('can:sales.orders.manage');
        Route::put('/{id}/cancellation-requests/{requestId}/review', [EcommerceOrderManagementController::class, 'reviewCancellationRequest'])->middleware('can:sales.orders.manage');
        Route::post('/{id}/assign-delivery', [EcommerceOrderManagementController::class, 'assignDelivery'])->middleware('can:sales.orders.manage');
        Route::put('/{id}/delivery-assignment', [EcommerceOrderManagementController::class, 'updateDeliveryAssignment'])->middleware('can:sales.orders.manage');
        Route::get('/{id}/branch-candidates', [EcommerceOrderManagementController::class, 'branchTransferCandidates'])->middleware('can:sales.orders.view');
        Route::post('/{id}/pass-branch', [EcommerceOrderManagementController::class, 'passToBranch'])->middleware('can:sales.orders.manage');
        Route::get('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'chatMessages'])->middleware('can:sales.orders.view');
        Route::post('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'sendChatMessage'])->middleware('can:sales.orders.manage');
    });

    Route::prefix('refunds')->group(function () {
        Route::post('/', [SalesRefundController::class, 'store'])->middleware('can:sales.refunds.manage');
    });

    Route::get('/reports/summary', [SalesReportsController::class, 'summary'])->middleware('can:sales.reports.view');
});
