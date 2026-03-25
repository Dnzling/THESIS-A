<?php

use App\Http\Controllers\Api\Sales\SalesCrmController;
use App\Http\Controllers\Api\Sales\SalesChatController;
use App\Http\Controllers\Api\Sales\SalesPosController;
use App\Http\Controllers\Api\Sales\SalesOrderDeliveryController;
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
    });

    Route::prefix('pos')->group(function () {
        Route::get('/products', [SalesPosController::class, 'products'])->middleware('can:sales.pos.view');
        Route::post('/checkout', [SalesPosController::class, 'checkout'])->middleware('can:sales.pos.manage');
        Route::get('/orders', [SalesPosController::class, 'orders'])->middleware('can:sales.pos.view');
        Route::get('/orders/{id}', [SalesPosController::class, 'show'])->middleware('can:sales.pos.view');
        Route::post('/orders/{id}/sync-payment', [SalesPosController::class, 'syncPayment'])->middleware('can:sales.pos.manage');
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
        Route::put('/{id}/status', [EcommerceOrderManagementController::class, 'updateStatus'])->middleware('can:sales.ecommerce-orders.manage');
        Route::post('/{id}/assign-delivery', [EcommerceOrderManagementController::class, 'assignDelivery'])->middleware('can:sales.ecommerce-orders.manage');
        Route::put('/{id}/delivery-assignment', [EcommerceOrderManagementController::class, 'updateDeliveryAssignment'])->middleware('can:sales.ecommerce-orders.manage');
        Route::get('/{id}/branch-candidates', [EcommerceOrderManagementController::class, 'branchTransferCandidates'])->middleware('can:sales.ecommerce-orders.view');
        Route::post('/{id}/pass-branch', [EcommerceOrderManagementController::class, 'passToBranch'])->middleware('can:sales.ecommerce-orders.manage');
        Route::get('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'chatMessages'])->middleware('can:sales.ecommerce-orders.view');
        Route::post('/{id}/chat/messages', [EcommerceOrderManagementController::class, 'sendChatMessage'])->middleware('can:sales.ecommerce-orders.manage');
    });
});
