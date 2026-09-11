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
    Route::get('/dashboard', [SalesPosController::class, 'dashboard']);
    Route::get('/analytics/payments', [SalesPosController::class, 'paymentAnalytics']);

    Route::prefix('crm')->group(function () {
        Route::get('/leads', [SalesCrmController::class, 'leads']);
        Route::post('/leads', [SalesCrmController::class, 'storeLead']);
        Route::put('/leads/{id}', [SalesCrmController::class, 'updateLead']);
        Route::post('/leads/{id}/stage', [SalesCrmController::class, 'changeStage']);
        Route::get('/leads/{id}/activities', [SalesCrmController::class, 'activities']);
        Route::post('/leads/{id}/activities', [SalesCrmController::class, 'addActivity']);
    });

    Route::prefix('chats')->group(function () {
        Route::get('/threads', [SalesChatController::class, 'threads']);
        Route::get('/threads/{id}/messages', [SalesChatController::class, 'messages']);
        Route::post('/threads/{id}/messages', [SalesChatController::class, 'sendMessage']);
        Route::put('/threads/{id}/messages/{messageId}', [SalesChatController::class, 'updateMessage']);
        Route::delete('/threads/{id}/messages/{messageId}', [SalesChatController::class, 'unsendMessage']);
    });

    Route::prefix('pos')->group(function () {
        Route::get('/products', [SalesPosController::class, 'products']);
        Route::post('/delivery-fee/estimate', [SalesPosController::class, 'estimateDeliveryFee']);
        Route::post('/checkout', [SalesPosController::class, 'checkout']);
        Route::get('/orders', [SalesPosController::class, 'orders']);
        Route::get('/orders/{id}', [SalesPosController::class, 'show']);
        Route::get('/orders/{id}/receipt', [SalesPosController::class, 'receiptPdf']);
        Route::post('/orders/{id}/sync-payment', [SalesPosController::class, 'syncPayment']);
        Route::post('/orders/{id}/send-to-logistics', [SalesPosController::class, 'sendToLogistics']);
    });

    Route::prefix('order-deliveries')->group(function () {
        Route::get('/drivers', [SalesOrderDeliveryController::class, 'drivers']);
        Route::get('/', [SalesOrderDeliveryController::class, 'index']);
        Route::get('/{id}', [SalesOrderDeliveryController::class, 'show']);
        Route::put('/{id}/status', [SalesOrderDeliveryController::class, 'updateStatus']);
        Route::post('/{id}/assign-driver', [SalesOrderDeliveryController::class, 'assignDriver']);
        Route::post('/{id}/proof', [SalesOrderDeliveryController::class, 'uploadProof']);
        Route::get('/{id}/logs', [SalesOrderDeliveryController::class, 'logs']);
        Route::post('/{id}/logs', [SalesOrderDeliveryController::class, 'addLog']);
    });

    Route::prefix('ecommerce-orders')->group(function () {
        Route::get('/', [EcommerceOrderManagementController::class, 'index']);
        Route::get('/{id}', [EcommerceOrderManagementController::class, 'show']);
        Route::get('/{id}/receipt', [EcommerceOrderManagementController::class, 'receiptPdf']);
        Route::put('/{id}/status', [EcommerceOrderManagementController::class, 'updateStatus']);
        Route::put('/{id}/cancellation-requests/{requestId}/review', [EcommerceOrderManagementController::class, 'reviewCancellationRequest']);
        Route::post('/{id}/assign-delivery', [EcommerceOrderManagementController::class, 'assignDelivery']);
        Route::put('/{id}/delivery-assignment', [EcommerceOrderManagementController::class, 'updateDeliveryAssignment']);
        Route::get('/{id}/branch-candidates', [EcommerceOrderManagementController::class, 'branchTransferCandidates']);
        Route::post('/{id}/pass-branch', [EcommerceOrderManagementController::class, 'passToBranch']);
        Route::get('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'chatMessages']);
        Route::post('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'sendChatMessage']);
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/', [SalesReviewController::class, 'index']);
        Route::get('/{review}', [SalesReviewController::class, 'show']);
        Route::put('/{review}/reply', [SalesReviewController::class, 'reply']);
    });

    Route::prefix('vouchers')->group(function () {
        Route::get('/', [SalesVoucherController::class, 'index']);
        Route::post('/', [SalesVoucherController::class, 'store']);
        Route::get('/{voucher}', [SalesVoucherController::class, 'show']);
        Route::put('/{voucher}', [SalesVoucherController::class, 'update']);
    });

    Route::prefix('refunds')->group(function () {
        Route::post('/', [SalesRefundController::class, 'store']);
    });

    Route::prefix('returns')->group(function () {
        Route::get('/', [SalesReturnController::class, 'index']);
        Route::get('/{return}', [SalesReturnController::class, 'show']);
        Route::put('/{return}/status', [SalesReturnController::class, 'updateStatus']);
        Route::post('/{return}/pickup', [SalesReturnController::class, 'createPickup']);
        Route::post('/{return}/receive', [SalesReturnController::class, 'receive']);
        Route::post('/{return}/refund', [SalesReturnController::class, 'refund']);
    });

    Route::get('/reports/summary', [SalesReportsController::class, 'summary']);
});
