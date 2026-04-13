<?php

use App\Http\Controllers\Api\Sales\SalesCrmController;
use App\Http\Controllers\Api\Sales\SalesChatController;
use App\Http\Controllers\Api\Sales\SalesPosController;
use App\Http\Controllers\Api\Sales\SalesOrderDeliveryController;
use App\Http\Controllers\Api\Sales\SalesReviewController;
use App\Http\Controllers\Api\Sales\SalesRefundController;
use App\Http\Controllers\Api\Sales\SalesReturnController;
use App\Http\Controllers\Api\Sales\SalesReportsController;
use App\Http\Controllers\Api\Sales\SalesVoucherController;
use App\Http\Controllers\Api\Inventory\EcommerceOrderManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->group(function () {
    Route::get('/dashboard', [SalesPosController::class, 'dashboard'])->middleware('can:sales.dashboard.view');
    Route::get('/analytics/payments', [SalesPosController::class, 'paymentAnalytics'])->middleware('can:sales.analytics.view');

    Route::prefix('crm')->group(function () {
        Route::get('/leads', [SalesCrmController::class, 'leads'])->middleware('can:sales.crm.view');
        Route::post('/leads', [SalesCrmController::class, 'storeLead'])->middleware('can:sales.crm.manage');
        Route::put('/leads/{id}', [SalesCrmController::class, 'updateLead'])->middleware('can:sales.crm.manage');
        Route::post('/leads/{id}/stage', [SalesCrmController::class, 'changeStage'])->middleware('can:sales.crm.manage');
        Route::get('/leads/{id}/activities', [SalesCrmController::class, 'activities'])->middleware('can:sales.crm.view');
        Route::post('/leads/{id}/activities', [SalesCrmController::class, 'addActivity'])->middleware('can:sales.crm.manage');
    });

    Route::prefix('chats')->group(function () {
        Route::get('/threads', [SalesChatController::class, 'threads'])->middleware('can:sales.chats.view');
        Route::get('/threads/{id}/messages', [SalesChatController::class, 'messages'])->middleware('can:sales.chats.view');
        Route::post('/threads/{id}/messages', [SalesChatController::class, 'sendMessage'])->middleware('can:sales.chats.manage');
        Route::put('/threads/{id}/messages/{messageId}', [SalesChatController::class, 'updateMessage'])->middleware('can:sales.chats.manage');
        Route::delete('/threads/{id}/messages/{messageId}', [SalesChatController::class, 'unsendMessage'])->middleware('can:sales.chats.manage');
    });

    Route::prefix('pos')->group(function () {
        Route::get('/products', [SalesPosController::class, 'products'])->middleware('can:sales.pos.view');
        Route::post('/checkout', [SalesPosController::class, 'checkout'])->middleware('can:sales.pos.manage');
        Route::get('/orders', [SalesPosController::class, 'orders'])->middleware('can:sales.pos.view');
        Route::get('/orders/{id}', [SalesPosController::class, 'show'])->middleware('can:sales.pos.view');
        Route::get('/orders/{id}/receipt', [SalesPosController::class, 'receiptPdf'])->middleware('can:sales.pos.view');
        Route::post('/orders/{id}/sync-payment', [SalesPosController::class, 'syncPayment'])->middleware('can:sales.pos.manage');
        Route::post('/orders/{id}/send-to-logistics', [SalesPosController::class, 'sendToLogistics'])->middleware('can:sales.order.approve');
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
        Route::get('/', [EcommerceOrderManagementController::class, 'index'])->middleware('can:sales.ecommerce-orders.view');
        Route::get('/{id}', [EcommerceOrderManagementController::class, 'show'])->middleware('can:sales.ecommerce-orders.view');
        Route::get('/{id}/receipt', [EcommerceOrderManagementController::class, 'receiptPdf'])->middleware('can:sales.ecommerce-orders.view');
        Route::put('/{id}/status', [EcommerceOrderManagementController::class, 'updateStatus'])->middleware('can:sales.ecommerce-orders.manage');
        Route::put('/{id}/cancellation-requests/{requestId}/review', [EcommerceOrderManagementController::class, 'reviewCancellationRequest'])->middleware('can:sales.ecommerce-orders.manage');
        Route::post('/{id}/assign-delivery', [EcommerceOrderManagementController::class, 'assignDelivery'])->middleware('can:sales.ecommerce-orders.manage');
        Route::put('/{id}/delivery-assignment', [EcommerceOrderManagementController::class, 'updateDeliveryAssignment'])->middleware('can:sales.ecommerce-orders.manage');
        Route::get('/{id}/branch-candidates', [EcommerceOrderManagementController::class, 'branchTransferCandidates'])->middleware('can:sales.ecommerce-orders.view');
        Route::post('/{id}/pass-branch', [EcommerceOrderManagementController::class, 'passToBranch'])->middleware('can:sales.ecommerce-orders.manage');
        Route::get('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'chatMessages'])->middleware('can:sales.ecommerce-orders.view');
        Route::post('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'sendChatMessage'])->middleware('can:sales.ecommerce-orders.manage');
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/', [SalesReviewController::class, 'index'])->middleware('can:sales.reviews.view');
        Route::get('/{review}', [SalesReviewController::class, 'show'])->middleware('can:sales.reviews.view');
        Route::put('/{review}/reply', [SalesReviewController::class, 'reply'])->middleware('can:sales.reviews.manage');
    });

    Route::prefix('vouchers')->group(function () {
        Route::get('/', [SalesVoucherController::class, 'index'])->middleware('can:sales.vouchers.view');
        Route::post('/', [SalesVoucherController::class, 'store'])->middleware('can:sales.vouchers.manage');
        Route::get('/{voucher}', [SalesVoucherController::class, 'show'])->middleware('can:sales.vouchers.view');
        Route::put('/{voucher}', [SalesVoucherController::class, 'update'])->middleware('can:sales.vouchers.manage');
    });

    Route::prefix('refunds')->group(function () {
        Route::get('/', [SalesRefundController::class, 'index'])->middleware('can:sales.refunds.view');
        Route::get('/{refund}', [SalesRefundController::class, 'show'])->middleware('can:sales.refunds.view');
        Route::post('/', [SalesRefundController::class, 'store'])->middleware('can:sales.refunds.manage');
        Route::put('/{refund}/status', [SalesRefundController::class, 'updateStatus'])->middleware('can:sales.refunds.manage');
    });

    Route::prefix('returns')->group(function () {
        Route::get('/', [SalesReturnController::class, 'index'])->middleware('can:sales.ecommerce-orders.view');
        Route::get('/{return}', [SalesReturnController::class, 'show'])->middleware('can:sales.ecommerce-orders.view');
        Route::put('/{return}/status', [SalesReturnController::class, 'updateStatus'])->middleware('can:sales.ecommerce-orders.manage');
        Route::post('/{return}/pickup', [SalesReturnController::class, 'createPickup'])->middleware('can:sales.ecommerce-orders.manage');
        Route::post('/{return}/receive', [SalesReturnController::class, 'receive'])->middleware('can:sales.ecommerce-orders.manage');
        Route::post('/{return}/refund', [SalesReturnController::class, 'refund'])->middleware('can:sales.ecommerce-orders.manage');
    });

    Route::get('/reports/summary', [SalesReportsController::class, 'summary'])->middleware('can:sales.reports.view');
});
