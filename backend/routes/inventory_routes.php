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
use App\Http\Controllers\Api\Inventory\ProductController;
use App\Http\Controllers\Api\Inventory\CategoryController;
use App\Http\Controllers\Api\Inventory\UnitController;
use App\Http\Controllers\Api\Inventory\StockIssueController;
use App\Http\Controllers\Api\Inventory\StockReturnController;
use App\Http\Controllers\Api\Inventory\StockCountController;
use App\Http\Controllers\Api\Inventory\WarehouseController;
use App\Http\Controllers\Api\Inventory\LocationController;
use App\Http\Controllers\Api\Inventory\ReorderRuleController;
use App\Http\Controllers\Api\Inventory\ReorderSuggestionController;
use App\Http\Controllers\Api\Inventory\SerialNumberController;
use App\Http\Controllers\Api\Inventory\BatchController;
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

    // Stock Returns
    Route::prefix('returns')->group(function () {
        Route::get('/', [StockReturnController::class, 'index']);
        Route::post('/', [StockReturnController::class, 'store']);
        Route::get('/{return}', [StockReturnController::class, 'show']);
        Route::put('/{return}', [StockReturnController::class, 'update']);
        Route::delete('/{return}', [StockReturnController::class, 'destroy']);
        Route::post('/{return}/approve', [StockReturnController::class, 'approve']);
        Route::post('/{return}/reject', [StockReturnController::class, 'reject']);
        Route::post('/{return}/ship', [StockReturnController::class, 'ship']);
        Route::post('/{return}/receive', [StockReturnController::class, 'receive']);
        Route::get('/reasons', [StockReturnController::class, 'getReasons']);
        Route::get('/types', [StockReturnController::class, 'getTypes']);
    });

    // Stock Counts
    Route::prefix('counts')->group(function () {
        Route::get('/', [StockCountController::class, 'index']);
        Route::post('/', [StockCountController::class, 'store']);
        Route::get('/{count}', [StockCountController::class, 'show']);
        Route::put('/{count}', [StockCountController::class, 'update']);
        Route::delete('/{count}', [StockCountController::class, 'destroy']);
        Route::post('/{count}/start', [StockCountController::class, 'start']);
        Route::post('/{count}/complete', [StockCountController::class, 'complete']);
        Route::post('/{count}/approve', [StockCountController::class, 'approve']);
        Route::get('/{count}/sheets', [StockCountController::class, 'getSheets']);
        Route::post('/{count}/update-counts', [StockCountController::class, 'updateCounts']);
        Route::get('/types', [StockCountController::class, 'getTypes']);
        Route::get('/statuses', [StockCountController::class, 'getStatuses']);
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

    // Products Management
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::get('/{id}/variations', [ProductController::class, 'getVariations']);
        Route::get('/{id}/stock-history', [ProductController::class, 'getStockHistory']);
    });

    // Categories Management
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
        Route::get('/{id}/products', [CategoryController::class, 'getProducts']);
    });

    // Units Management
    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::post('/', [UnitController::class, 'store']);
        Route::put('/{id}', [UnitController::class, 'update']);
        Route::delete('/{id}', [UnitController::class, 'destroy']);
    });

    // Stock Issues
    Route::prefix('issues')->group(function () {
        Route::get('/', [StockIssueController::class, 'index']);
        Route::get('/{id}', [StockIssueController::class, 'show']);
        Route::post('/', [StockIssueController::class, 'store']);
        Route::post('/{id}/approve', [StockIssueController::class, 'approve']);
        Route::get('/reasons', [StockIssueController::class, 'getReasons']);
    });

    // Warehouses Management
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::post('/', [WarehouseController::class, 'store']);
        Route::get('/{warehouse}', [WarehouseController::class, 'show']);
        Route::put('/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy']);
        Route::get('/types', [WarehouseController::class, 'getTypes']);
        Route::get('/stats', [WarehouseController::class, 'getStats']);
        Route::get('/capacity-utilization', [WarehouseController::class, 'getCapacityUtilization']);
    });

    // Locations Management
    Route::prefix('locations')->group(function () {
        Route::get('/', [LocationController::class, 'index']);
        Route::post('/', [LocationController::class, 'store']);
        Route::get('/{location}', [LocationController::class, 'show']);
        Route::put('/{location}', [LocationController::class, 'update']);
        Route::delete('/{location}', [LocationController::class, 'destroy']);
        Route::get('/types', [LocationController::class, 'getTypes']);
        Route::get('/available', [LocationController::class, 'getAvailable']);
        Route::post('/{location}/update-stock', [LocationController::class, 'updateStock']);
        Route::get('/needing-check', [LocationController::class, 'getNeedingCheck']);
    });

    // Reorder Rules Management
    Route::prefix('reorder-rules')->group(function () {
        Route::get('/', [ReorderRuleController::class, 'index']);
        Route::post('/', [ReorderRuleController::class, 'store']);
        Route::get('/{reorder_rule}', [ReorderRuleController::class, 'show']);
        Route::put('/{reorder_rule}', [ReorderRuleController::class, 'update']);
        Route::delete('/{reorder_rule}', [ReorderRuleController::class, 'destroy']);
        Route::get('/rule-types', [ReorderRuleController::class, 'getRuleTypes']);
        Route::get('/trigger-types', [ReorderRuleController::class, 'getTriggerTypes']);
        Route::get('/priorities', [ReorderRuleController::class, 'getPriorities']);
        Route::post('/check-status', [ReorderRuleController::class, 'checkReorderStatus']);
        Route::post('/generate-suggestions', [ReorderRuleController::class, 'generateSuggestions']);
        Route::get('/needing-review', [ReorderRuleController::class, 'getNeedingReview']);
        Route::post('/bulk-update-priority', [ReorderRuleController::class, 'bulkUpdatePriority']);
    });

    // Reorder Suggestions Management
    Route::prefix('reorder-suggestions')->group(function () {
        Route::get('/', [ReorderSuggestionController::class, 'index']);
        Route::post('/', [ReorderSuggestionController::class, 'store']);
        Route::get('/{suggestion}', [ReorderSuggestionController::class, 'show']);
        Route::put('/{suggestion}', [ReorderSuggestionController::class, 'update']);
        Route::delete('/{suggestion}', [ReorderSuggestionController::class, 'destroy']);
        Route::post('/{suggestion}/approve', [ReorderSuggestionController::class, 'approve']);
        Route::post('/{suggestion}/reject', [ReorderSuggestionController::class, 'reject']);
        Route::post('/{suggestion}/implement', [ReorderSuggestionController::class, 'implement']);
        Route::post('/{suggestion}/cancel', [ReorderSuggestionController::class, 'cancel']);
        Route::post('/generate', [ReorderSuggestionController::class, 'generateSuggestions']);
        Route::get('/stats/overview', [ReorderSuggestionController::class, 'getStats']);
        Route::post('/bulk-approve', [ReorderSuggestionController::class, 'bulkApprove']);
        Route::post('/bulk-reject', [ReorderSuggestionController::class, 'bulkReject']);
        Route::get('/types', [ReorderSuggestionController::class, 'getTypes']);
    });

    // Serial Numbers Management
    Route::prefix('serial-numbers')->group(function () {
        Route::get('/', [SerialNumberController::class, 'index']);
        Route::post('/', [SerialNumberController::class, 'store']);
        Route::get('/{serial_number}', [SerialNumberController::class, 'show']);
        Route::put('/{serial_number}', [SerialNumberController::class, 'update']);
        Route::delete('/{serial_number}', [SerialNumberController::class, 'destroy']);
        Route::post('/{serial_number}/sell', [SerialNumberController::class, 'sell']);
        Route::post('/{serial_number}/reserve', [SerialNumberController::class, 'reserve']);
        Route::post('/{serial_number}/unreserve', [SerialNumberController::class, 'unreserve']);
        Route::post('/{serial_number}/mark-damaged', [SerialNumberController::class, 'markAsDamaged']);
        Route::post('/{serial_number}/return', [SerialNumberController::class, 'returnSerialNumber']);
        Route::post('/{serial_number}/move-location', [SerialNumberController::class, 'moveToLocation']);
        Route::get('/stats/overview', [SerialNumberController::class, 'getStats']);
        Route::get('/expiring-warranties', [SerialNumberController::class, 'getExpiringWarranties']);
        Route::get('/expired-warranties', [SerialNumberController::class, 'getExpiredWarranties']);
        Route::post('/generate-next', [SerialNumberController::class, 'generateNextSerialNumber']);
        Route::post('/check-exists', [SerialNumberController::class, 'checkSerialNumber']);
        Route::get('/by-product', [SerialNumberController::class, 'getByProduct']);
        Route::post('/transfer', [SerialNumberController::class, 'transferSerialNumbers']);
        Route::post('/bulk-import', [SerialNumberController::class, 'bulkImport']);
        Route::get('/types', [SerialNumberController::class, 'getTypes']);
    });

    // Batch Management
    Route::prefix('batches')->group(function () {
        Route::get('/', [BatchController::class, 'index']);
        Route::post('/', [BatchController::class, 'store']);
        Route::get('/{batch}', [BatchController::class, 'show']);
        Route::put('/{batch}', [BatchController::class, 'update']);
        Route::delete('/{batch}', [BatchController::class, 'destroy']);
        Route::post('/{batch}/reserve-stock', [BatchController::class, 'reserveStock']);
        Route::post('/{batch}/unreserve-stock', [BatchController::class, 'unreserveStock']);
        Route::post('/{batch}/sell-stock', [BatchController::class, 'sellStock']);
        Route::post('/{batch}/return-stock', [BatchController::class, 'returnStock']);
        Route::post('/{batch}/mark-damaged', [BatchController::class, 'markAsDamaged']);
        Route::post('/{batch}/move-location', [BatchController::class, 'moveToLocation']);
        Route::post('/{batch}/approve-quality', [BatchController::class, 'approveQuality']);
        Route::post('/{batch}/reject-quality', [BatchController::class, 'rejectQuality']);
        Route::post('/{batch}/quarantine', [BatchController::class, 'quarantineBatch']);
        Route::get('/stats/overview', [BatchController::class, 'getStats']);
        Route::get('/expiring', [BatchController::class, 'getExpiringBatches']);
        Route::get('/best-before', [BatchController::class, 'getBestBeforeBatches']);
        Route::post('/generate-next', [BatchController::class, 'generateNextBatchNumber']);
        Route::post('/check-exists', [BatchController::class, 'checkBatchNumber']);
        Route::get('/by-product', [BatchController::class, 'getByProduct']);
        Route::post('/transfer', [BatchController::class, 'transferBatches']);
        Route::post('/bulk-import', [BatchController::class, 'bulkImport']);
        Route::post('/update-statuses', [BatchController::class, 'updateBatchStatuses']);
        Route::get('/types', [BatchController::class, 'getTypes']);
    });
});