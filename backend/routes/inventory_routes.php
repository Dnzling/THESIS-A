<?php

use Illuminate\Support\Facades\Route;

// ============================================
// INVENTORY ROUTES
// ============================================
use App\Http\Controllers\Api\Inventory\BranchInventoryController;
use App\Http\Controllers\Api\Inventory\DashboardController;
use App\Http\Controllers\Api\Inventory\InventoryTransactionController;
use App\Http\Controllers\Api\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\Inventory\StockTransferController;
use App\Http\Controllers\Api\Inventory\StockAlertController;
use App\Http\Controllers\Api\Inventory\NotificationController;
use App\Http\Controllers\Api\Inventory\AlertController;
use App\Http\Controllers\Api\Inventory\InventoryConfigurationController;
use App\Http\Controllers\Api\Inventory\InventoryReportController;
use App\Http\Controllers\Api\Store\BranchController;

// ============================================
// INVENTORY MANAGEMENT ROUTES
// ============================================
Route::prefix('inventory')->group(function () {

    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/summary', [DashboardController::class, 'getSummaryCards']);
        Route::get('/', [DashboardController::class, 'getUserDashboard']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'getUnread']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'delete']);
        Route::post('/batch-delete', [NotificationController::class, 'batchDelete']);
    });

    // Enhanced Alerts (new AlertController)
    Route::prefix('alert-management')->group(function () {
        Route::get('/', [AlertController::class, 'index']);
        Route::get('/active', [AlertController::class, 'getActive']);
        Route::get('/by-type', [AlertController::class, 'getByType']);
        Route::get('/statistics', [AlertController::class, 'statistics']);
        Route::get('/{id}', [AlertController::class, 'show']);
        Route::post('/{id}/acknowledge', [AlertController::class, 'acknowledge']);
        Route::post('/{id}/resolve', [AlertController::class, 'resolve']);
    });

    // Configuration
    Route::prefix('configuration')->group(function () {
        Route::get('/', [InventoryConfigurationController::class, 'show']);
        Route::put('/', [InventoryConfigurationController::class, 'update']);
        Route::get('/schema', [InventoryConfigurationController::class, 'schema']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/branch-summary', [InventoryReportController::class, 'branchSummary']);
        Route::get('/store-summary', [InventoryReportController::class, 'storeSummary']);
        Route::get('/movements', [InventoryReportController::class, 'movements']);
        Route::get('/value-by-category', [InventoryReportController::class, 'valueByCategory']);
        Route::get('/slow-movers', [InventoryReportController::class, 'slowMovers']);
        Route::get('/fast-movers', [InventoryReportController::class, 'fastMovers']);
        Route::get('/transfers', [InventoryReportController::class, 'transfers']);
        Route::get('/aging', [InventoryReportController::class, 'aging']);
    });

    // Branch Inventory
    Route::get('/branches', [BranchController::class, 'index']);
    Route::prefix('branch/{branchId}')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index']);
        Route::get('/summary', [BranchInventoryController::class, 'summary']);
        Route::get('/low-stock', action: [BranchInventoryController::class, 'lowStock']);
    });

    Route::prefix('items')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index']);
        Route::get('/{id}', [BranchInventoryController::class, 'show']);
        Route::post('/', [BranchInventoryController::class, 'store']);
        Route::put('/{id}', [BranchInventoryController::class, 'update']);
        Route::delete('/{id}', [BranchInventoryController::class, 'destroy']);
        Route::post('/{id}/update-status', [BranchInventoryController::class, 'updateStatus']);
    });

    // Inventory Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [InventoryTransactionController::class, 'index']);
        Route::get('/summary', [InventoryTransactionController::class, 'summary']);
        Route::get('/product/{productId}', [InventoryTransactionController::class, 'productHistory']);
        Route::get('/export', [InventoryTransactionController::class, 'export']);
        Route::get('/chart', [InventoryTransactionController::class, 'chartData']);
        Route::get('/recent', [InventoryTransactionController::class, 'recent']);
        Route::get('/{id}', [InventoryTransactionController::class, 'show']);
    });

    // Stock Adjustments
    Route::prefix('adjustments')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index']);
        Route::get('/{id}', [StockAdjustmentController::class, 'show']);
        Route::post('/', [StockAdjustmentController::class, 'store']);
        Route::post('/{id}/submit', [StockAdjustmentController::class, 'submit']);
        Route::post('/{id}/approve', [StockAdjustmentController::class, 'approve']);
        Route::post('/{id}/reject', [StockAdjustmentController::class, 'reject']);
    });

    // Stock Transfers
    Route::prefix('transfers')->group(function () {
        Route::get('/', [StockTransferController::class, 'index']);
        Route::get('/{id}', [StockTransferController::class, 'show']);
        Route::post('/', [StockTransferController::class, 'store']);
        Route::post('/{id}/approve', [StockTransferController::class, 'approve']);
        Route::post('/{id}/ship', [StockTransferController::class, 'ship']);
        Route::post('/{id}/receive', [StockTransferController::class, 'receive']);
        Route::post('/{id}/cancel', [StockTransferController::class, 'cancel']);
    });

    // Stock Alerts
    Route::prefix('alerts')->group(function () {
        Route::get('/', [StockAlertController::class, 'index']);
        Route::get('/{id}', [StockAlertController::class, 'show']);
        Route::get('/summary', [StockAlertController::class, 'summary']);
        Route::post('/{id}/acknowledge', [StockAlertController::class, 'acknowledge']);
        Route::post('/{id}/resolve', [StockAlertController::class, 'resolve']);
        Route::post('/bulk-acknowledge', [StockAlertController::class, 'bulkAcknowledge']);
        Route::post('/bulk-resolve', [StockAlertController::class, 'bulkResolve']);
        Route::post('/generate', [StockAlertController::class, 'generateAlerts']);
        Route::delete('/{id}', [StockAlertController::class, 'destroy']);
    });
});