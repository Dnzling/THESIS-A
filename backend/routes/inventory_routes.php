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
use App\Http\Controllers\Api\Inventory\EcommerceOrderManagementController;
use App\Http\Controllers\Api\Inventory\EcommerceDeliveryController;
use App\Http\Controllers\Api\Inventory\EcommerceDeliveryVehicleController;
use App\Http\Controllers\Api\Inventory\InventoryActivityLogController;
use App\Http\Controllers\Api\Store\BranchController;

// ============================================
// INVENTORY MANAGEMENT ROUTES
// ============================================
Route::prefix('inventory')->group(function () {

    Route::prefix('dashboard')->middleware('can:inventory.dashboard.view')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/summary', [DashboardController::class, 'getSummaryCards']);
        Route::get('/', [DashboardController::class, 'getUserDashboard']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->middleware('can:inventory.notifications.view');
        Route::get('/unread', [NotificationController::class, 'getUnread'])->middleware('can:inventory.notifications.view');
        Route::get('/{id}', [NotificationController::class, 'show'])->middleware('can:inventory.notifications.view');
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('can:inventory.notifications.manage');
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->middleware('can:inventory.notifications.manage');
        Route::delete('/{id}', [NotificationController::class, 'delete'])->middleware('can:inventory.notifications.manage');
        Route::post('/batch-delete', [NotificationController::class, 'batchDelete'])->middleware('can:inventory.notifications.manage');
    });

    // Enhanced Alerts (new AlertController)
    Route::prefix('alert-management')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->middleware('can:inventory.alerts.view');
        Route::get('/active', [AlertController::class, 'getActive'])->middleware('can:inventory.alerts.view');
        Route::get('/by-type', [AlertController::class, 'getByType'])->middleware('can:inventory.alerts.view');
        Route::get('/statistics', [AlertController::class, 'statistics'])->middleware('can:inventory.alerts.view');
        Route::get('/{id}', [AlertController::class, 'show'])->middleware('can:inventory.alerts.view');
        Route::post('/{id}/acknowledge', [AlertController::class, 'acknowledge'])->middleware('can:inventory.alerts.acknowledge');
        Route::post('/{id}/resolve', [AlertController::class, 'resolve'])->middleware('can:inventory.alerts.resolve');
    });

    // Configuration
    Route::prefix('configuration')->middleware('can:inventory.configuration.manage')->group(function () {
        Route::get('/', [InventoryConfigurationController::class, 'show']);
        Route::put('/', [InventoryConfigurationController::class, 'update']);
        Route::get('/schema', [InventoryConfigurationController::class, 'schema']);
    });

    // Reports
    Route::prefix('reports')->middleware('can:inventory.reports.view')->group(function () {
        Route::get('/branch-summary', [InventoryReportController::class, 'branchSummary']);
        Route::get('/store-summary', [InventoryReportController::class, 'storeSummary'])->middleware('can:inventory.reports.view_all_branches');
        Route::get('/movements', [InventoryReportController::class, 'movements']);
        Route::get('/value-by-category', [InventoryReportController::class, 'valueByCategory']);
        Route::get('/slow-movers', [InventoryReportController::class, 'slowMovers']);
        Route::get('/fast-movers', [InventoryReportController::class, 'fastMovers']);
        Route::get('/transfers', [InventoryReportController::class, 'transfers']);
        Route::get('/aging', [InventoryReportController::class, 'aging']);
    });

    // Branch Inventory
    Route::get('/branches', [BranchController::class, 'index']);
    Route::prefix('branch/{branchId}')->middleware('can:inventory.branch_inventory.view')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index']);
        Route::get('/summary', [BranchInventoryController::class, 'summary']);
        Route::get('/low-stock', action: [BranchInventoryController::class, 'lowStock']);
    });

    Route::prefix('items')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index'])->middleware('can:inventory.items.view');
        Route::get('/{id}', [BranchInventoryController::class, 'show'])->middleware('can:inventory.items.view');
        Route::post('/', [BranchInventoryController::class, 'store'])->middleware('can:inventory.items.create');
        Route::put('/{id}', [BranchInventoryController::class, 'update'])->middleware('can:inventory.items.update');
        Route::delete('/{id}', [BranchInventoryController::class, 'destroy'])->middleware('can:inventory.items.delete');
        Route::post('/{id}/update-status', [BranchInventoryController::class, 'updateStatus'])->middleware('can:inventory.branch_inventory.manage');
    });

    // Inventory Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [InventoryTransactionController::class, 'index'])->middleware('can:inventory.transactions.view');
        Route::get('/summary', [InventoryTransactionController::class, 'summary'])->middleware('can:inventory.transactions.view');
        Route::get('/product/{productId}', [InventoryTransactionController::class, 'productHistory'])->middleware('can:inventory.transactions.view');
        Route::get('/export', [InventoryTransactionController::class, 'export'])->middleware('can:inventory.transactions.export');
        Route::get('/chart', [InventoryTransactionController::class, 'chartData'])->middleware('can:inventory.transactions.view');
        Route::get('/recent', [InventoryTransactionController::class, 'recent'])->middleware('can:inventory.transactions.view');
        Route::get('/{id}', [InventoryTransactionController::class, 'show'])->middleware('can:inventory.transactions.view');
    });

    // Inventory Activity Logs
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [InventoryActivityLogController::class, 'index'])->middleware('can:inventory.transactions.view');
        Route::get('/{id}', [InventoryActivityLogController::class, 'show'])->middleware('can:inventory.transactions.view');
    });

    // Stock Adjustments
    Route::prefix('adjustments')->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index'])->middleware('can:inventory.adjustments.view');
        Route::get('/{id}', [StockAdjustmentController::class, 'show'])->middleware('can:inventory.adjustments.view');
        Route::post('/', [StockAdjustmentController::class, 'store'])->middleware('can:inventory.adjustments.create');
        Route::post('/{id}/submit', [StockAdjustmentController::class, 'submit'])->middleware('can:inventory.adjustments.submit');
        Route::post('/{id}/approve', [StockAdjustmentController::class, 'approve'])->middleware('can:inventory.adjustments.approve');
        Route::post('/{id}/reject', [StockAdjustmentController::class, 'reject'])->middleware('can:inventory.adjustments.reject');
    });

    // Stock Transfers
    Route::prefix('transfers')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->middleware('can:inventory.transfers.view');
        Route::get('/{id}', [StockTransferController::class, 'show'])->middleware('can:inventory.transfers.view');
        Route::post('/', [StockTransferController::class, 'store'])->middleware('can:inventory.transfers.create');
        Route::post('/{id}/approve', [StockTransferController::class, 'approve'])->middleware('can:inventory.transfers.approve');
        Route::post('/{id}/ship', [StockTransferController::class, 'ship'])->middleware('can:inventory.transfers.ship');
        Route::post('/{id}/receive', [StockTransferController::class, 'receive'])->middleware('can:inventory.transfers.receive');
        Route::post('/{id}/cancel', [StockTransferController::class, 'cancel'])->middleware('can:inventory.transfers.cancel');
    });

    // Stock Returns
    Route::prefix('returns')->group(function () {
        Route::get('/', [StockReturnController::class, 'index'])->middleware('can:inventory.stock-returns.view');
        Route::post('/', [StockReturnController::class, 'store'])->middleware('can:inventory.stock-returns.create');
        Route::get('/{return}', [StockReturnController::class, 'show'])->middleware('can:inventory.stock-returns.view');
        Route::put('/{return}', [StockReturnController::class, 'update'])->middleware('can:inventory.stock-returns.update');
        Route::delete('/{return}', [StockReturnController::class, 'destroy'])->middleware('can:inventory.stock-returns.delete');
        Route::post('/{return}/approve', [StockReturnController::class, 'approve'])->middleware('can:inventory.stock-returns.approve');
        Route::post('/{return}/reject', [StockReturnController::class, 'reject'])->middleware('can:inventory.stock-returns.reject');
        Route::post('/{return}/ship', [StockReturnController::class, 'ship'])->middleware('can:inventory.stock-returns.ship');
        Route::post('/{return}/receive', [StockReturnController::class, 'receive'])->middleware('can:inventory.stock-returns.receive');
        Route::get('/reasons', [StockReturnController::class, 'getReasons'])->middleware('can:inventory.stock-returns.view');
        Route::get('/types', [StockReturnController::class, 'getTypes'])->middleware('can:inventory.stock-returns.view');
    });

    // Stock Counts
    Route::prefix('counts')->group(function () {
        Route::get('/', [StockCountController::class, 'index'])->middleware('can:inventory.stock-counts.view');
        Route::post('/', [StockCountController::class, 'store'])->middleware('can:inventory.stock-counts.create');
        Route::get('/suggestions', [StockCountController::class, 'suggestions'])->middleware('can:inventory.stock-counts.view');
        Route::post('/auto-schedule', [StockCountController::class, 'autoSchedule'])->middleware('can:inventory.stock-counts.create');
        Route::get('/{count}', [StockCountController::class, 'show'])->middleware('can:inventory.stock-counts.view');
        Route::put('/{count}', [StockCountController::class, 'update'])->middleware('can:inventory.stock-counts.update');
        Route::delete('/{count}', [StockCountController::class, 'destroy'])->middleware('can:inventory.stock-counts.delete');
        Route::post('/{count}/start', [StockCountController::class, 'start'])->middleware('can:inventory.stock-counts.start');
        Route::post('/{count}/complete', [StockCountController::class, 'complete'])->middleware('can:inventory.stock-counts.complete');
        Route::post('/{count}/approve', [StockCountController::class, 'approve'])->middleware('can:inventory.stock-counts.approve');
        Route::get('/{count}/sheets', [StockCountController::class, 'getSheets'])->middleware('can:inventory.stock-counts.view');
        Route::post('/{count}/update-counts', [StockCountController::class, 'updateCounts'])->middleware('can:inventory.stock-counts.update');
        Route::get('/types', [StockCountController::class, 'getTypes'])->middleware('can:inventory.stock-counts.view');
        Route::get('/statuses', [StockCountController::class, 'getStatuses'])->middleware('can:inventory.stock-counts.view');
    });

    // Stock Alerts
    Route::prefix('alerts')->group(function () {
        Route::get('/', [StockAlertController::class, 'index'])->middleware('can:inventory.alerts.view');
        Route::get('/{id}', [StockAlertController::class, 'show'])->middleware('can:inventory.alerts.view');
        Route::get('/summary', [StockAlertController::class, 'summary'])->middleware('can:inventory.alerts.view');
        Route::post('/{id}/acknowledge', [StockAlertController::class, 'acknowledge'])->middleware('can:inventory.alerts.acknowledge');
        Route::post('/{id}/resolve', [StockAlertController::class, 'resolve'])->middleware('can:inventory.alerts.resolve');
        Route::post('/bulk-acknowledge', [StockAlertController::class, 'bulkAcknowledge'])->middleware('can:inventory.alerts.acknowledge');
        Route::post('/bulk-resolve', [StockAlertController::class, 'bulkResolve'])->middleware('can:inventory.alerts.resolve');
        Route::post('/generate', [StockAlertController::class, 'generateAlerts'])->middleware('can:inventory.alerts.generate');
        Route::delete('/{id}', [StockAlertController::class, 'destroy'])->middleware('can:inventory.alerts.delete');
    });

    // Products Management
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware('can:inventory.products.view');
        Route::get('/{id}', [ProductController::class, 'show'])->middleware('can:inventory.products.view');
        Route::post('/', [ProductController::class, 'store'])->middleware('can:inventory.products.create');
        Route::put('/{id}', [ProductController::class, 'update'])->middleware('can:inventory.products.update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('can:inventory.products.delete');
        Route::get('/{id}/variations', [ProductController::class, 'getVariations'])->middleware('can:inventory.products.view');
        Route::get('/{id}/stock-history', [ProductController::class, 'getStockHistory'])->middleware('can:inventory.products.view');
    });

    // Categories Management
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->middleware('can:inventory.categories.view');
        Route::post('/', [CategoryController::class, 'store'])->middleware('can:inventory.categories.create');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('can:inventory.categories.update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('can:inventory.categories.delete');
        Route::get('/{id}/products', [CategoryController::class, 'getProducts'])->middleware('can:inventory.categories.view');
    });

    // Units Management
    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->middleware('can:inventory.units.view');
        Route::post('/', [UnitController::class, 'store'])->middleware('can:inventory.units.create');
        Route::put('/{id}', [UnitController::class, 'update'])->middleware('can:inventory.units.update');
        Route::delete('/{id}', [UnitController::class, 'destroy'])->middleware('can:inventory.units.delete');
    });

    // Stock Issues
    Route::prefix('issues')->group(function () {
        Route::get('/', [StockIssueController::class, 'index'])->middleware('can:inventory.stock-issues.view');
        Route::get('/{id}', [StockIssueController::class, 'show'])->middleware('can:inventory.stock-issues.view');
        Route::post('/', [StockIssueController::class, 'store'])->middleware('can:inventory.stock-issues.create');
        Route::post('/{id}/approve', [StockIssueController::class, 'approve'])->middleware('can:inventory.stock-issues.approve');
        Route::get('/reasons', [StockIssueController::class, 'getReasons'])->middleware('can:inventory.stock-issues.view');
    });

    // Warehouses Management
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->middleware('can:inventory.warehouses.view');
        Route::post('/', [WarehouseController::class, 'store'])->middleware('can:inventory.warehouses.create');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->middleware('can:inventory.warehouses.view');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->middleware('can:inventory.warehouses.update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('can:inventory.warehouses.delete');
        Route::get('/types', [WarehouseController::class, 'getTypes'])->middleware('can:inventory.warehouses.view');
        Route::get('/stats', [WarehouseController::class, 'getStats'])->middleware('can:inventory.warehouses.view');
        Route::get('/capacity-utilization', [WarehouseController::class, 'getCapacityUtilization'])->middleware('can:inventory.warehouses.view');
    });

    // Locations Management
    Route::prefix('locations')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->middleware('can:inventory.locations.view');
        Route::post('/', [LocationController::class, 'store'])->middleware('can:inventory.locations.create');
        Route::get('/{location}', [LocationController::class, 'show'])->middleware('can:inventory.locations.view');
        Route::put('/{location}', [LocationController::class, 'update'])->middleware('can:inventory.locations.update');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->middleware('can:inventory.locations.delete');
        Route::get('/types', [LocationController::class, 'getTypes'])->middleware('can:inventory.locations.view');
        Route::get('/available', [LocationController::class, 'getAvailable'])->middleware('can:inventory.locations.view');
        Route::post('/{location}/update-stock', [LocationController::class, 'updateStock'])->middleware('can:inventory.locations.update');
        Route::get('/needing-check', [LocationController::class, 'getNeedingCheck'])->middleware('can:inventory.locations.view');
    });

    // Reorder Rules Management
    Route::prefix('reorder-rules')->group(function () {
        Route::get('/', [ReorderRuleController::class, 'index'])->middleware('can:inventory.reorder-rules.view');
        Route::post('/', [ReorderRuleController::class, 'store'])->middleware('can:inventory.reorder-rules.create');
        Route::get('/rule-types', [ReorderRuleController::class, 'getRuleTypes'])->middleware('can:inventory.reorder-rules.view');
        Route::get('/trigger-types', [ReorderRuleController::class, 'getTriggerTypes'])->middleware('can:inventory.reorder-rules.view');
        Route::get('/basis-types', [ReorderRuleController::class, 'getBasisTypes'])->middleware('can:inventory.reorder-rules.view');
        Route::get('/priorities', [ReorderRuleController::class, 'getPriorities'])->middleware('can:inventory.reorder-rules.view');
        Route::post('/check-status', [ReorderRuleController::class, 'checkReorderStatus'])->middleware('can:inventory.reorder-rules.view');
        Route::post('/generate-suggestions', [ReorderRuleController::class, 'generateSuggestions'])->middleware('can:inventory.reorder-rules.update');
        Route::post('/auto-create', [ReorderRuleController::class, 'autoCreateFromInventory'])->middleware('can:inventory.reorder-rules.create');
        Route::get('/needing-review', [ReorderRuleController::class, 'getNeedingReview'])->middleware('can:inventory.reorder-rules.view');
        Route::post('/bulk-update-priority', [ReorderRuleController::class, 'bulkUpdatePriority'])->middleware('can:inventory.reorder-rules.update');
        Route::get('/{reorder_rule}', [ReorderRuleController::class, 'show'])->middleware('can:inventory.reorder-rules.view');
        Route::put('/{reorder_rule}', [ReorderRuleController::class, 'update'])->middleware('can:inventory.reorder-rules.update');
        Route::delete('/{reorder_rule}', [ReorderRuleController::class, 'destroy'])->middleware('can:inventory.reorder-rules.delete');
    });

    // Reorder Suggestions Management
    Route::prefix('reorder-suggestions')->group(function () {
        Route::get('/', [ReorderSuggestionController::class, 'index'])->middleware('can:inventory.reorder-suggestions.view');
        Route::post('/', [ReorderSuggestionController::class, 'store'])->middleware('can:inventory.reorder-suggestions.create');
        Route::get('/{suggestion}', [ReorderSuggestionController::class, 'show'])->middleware('can:inventory.reorder-suggestions.view');
        Route::put('/{suggestion}', [ReorderSuggestionController::class, 'update'])->middleware('can:inventory.reorder-suggestions.update');
        Route::delete('/{suggestion}', [ReorderSuggestionController::class, 'destroy'])->middleware('can:inventory.reorder-suggestions.delete');
        Route::post('/{suggestion}/approve', [ReorderSuggestionController::class, 'approve'])->middleware('can:inventory.reorder-suggestions.approve');
        Route::post('/{suggestion}/reject', [ReorderSuggestionController::class, 'reject'])->middleware('can:inventory.reorder-suggestions.reject');
        Route::post('/{suggestion}/implement', [ReorderSuggestionController::class, 'implement'])->middleware('can:inventory.reorder-suggestions.implement');
        Route::post('/{suggestion}/cancel', [ReorderSuggestionController::class, 'cancel'])->middleware('can:inventory.reorder-suggestions.update');
        Route::post('/generate', [ReorderSuggestionController::class, 'generateSuggestions'])->middleware('can:inventory.reorder-suggestions.create');
        Route::get('/stats/overview', [ReorderSuggestionController::class, 'getStats'])->middleware('can:inventory.reorder-suggestions.view');
        Route::post('/bulk-approve', [ReorderSuggestionController::class, 'bulkApprove'])->middleware('can:inventory.reorder-suggestions.approve');
        Route::post('/bulk-reject', [ReorderSuggestionController::class, 'bulkReject'])->middleware('can:inventory.reorder-suggestions.reject');
        Route::get('/types', [ReorderSuggestionController::class, 'getTypes'])->middleware('can:inventory.reorder-suggestions.view');
    });

    // Serial Numbers Management
    Route::prefix('serial-numbers')->group(function () {
        Route::get('/', [SerialNumberController::class, 'index'])->middleware('can:inventory.serial-numbers.view');
        Route::post('/', [SerialNumberController::class, 'store'])->middleware('can:inventory.serial-numbers.create');
        Route::get('/{serial_number}', [SerialNumberController::class, 'show'])->middleware('can:inventory.serial-numbers.view');
        Route::put('/{serial_number}', [SerialNumberController::class, 'update'])->middleware('can:inventory.serial-numbers.update');
        Route::delete('/{serial_number}', [SerialNumberController::class, 'destroy'])->middleware('can:inventory.serial-numbers.delete');
        Route::post('/{serial_number}/sell', [SerialNumberController::class, 'sell'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/{serial_number}/reserve', [SerialNumberController::class, 'reserve'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/{serial_number}/unreserve', [SerialNumberController::class, 'unreserve'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/{serial_number}/mark-damaged', [SerialNumberController::class, 'markAsDamaged'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/{serial_number}/return', [SerialNumberController::class, 'returnSerialNumber'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/{serial_number}/move-location', [SerialNumberController::class, 'moveToLocation'])->middleware('can:inventory.serial-numbers.update');
        Route::get('/stats/overview', [SerialNumberController::class, 'getStats'])->middleware('can:inventory.serial-numbers.view');
        Route::get('/expiring-warranties', [SerialNumberController::class, 'getExpiringWarranties'])->middleware('can:inventory.serial-numbers.view');
        Route::get('/expired-warranties', [SerialNumberController::class, 'getExpiredWarranties'])->middleware('can:inventory.serial-numbers.view');
        Route::post('/generate-next', [SerialNumberController::class, 'generateNextSerialNumber'])->middleware('can:inventory.serial-numbers.create');
        Route::post('/check-exists', [SerialNumberController::class, 'checkSerialNumber'])->middleware('can:inventory.serial-numbers.view');
        Route::get('/by-product', [SerialNumberController::class, 'getByProduct'])->middleware('can:inventory.serial-numbers.view');
        Route::post('/transfer', [SerialNumberController::class, 'transferSerialNumbers'])->middleware('can:inventory.serial-numbers.update');
        Route::post('/bulk-import', [SerialNumberController::class, 'bulkImport'])->middleware('can:inventory.serial-numbers.create');
        Route::get('/types', [SerialNumberController::class, 'getTypes'])->middleware('can:inventory.serial-numbers.view');
    });

    // Batch Management
    Route::prefix('batches')->group(function () {
        Route::get('/', [BatchController::class, 'index'])->middleware('can:inventory.batches.view');
        Route::post('/', [BatchController::class, 'store'])->middleware('can:inventory.batches.create');
        Route::get('/{batch}', [BatchController::class, 'show'])->middleware('can:inventory.batches.view');
        Route::put('/{batch}', [BatchController::class, 'update'])->middleware('can:inventory.batches.update');
        Route::delete('/{batch}', [BatchController::class, 'destroy'])->middleware('can:inventory.batches.delete');
        Route::post('/{batch}/reserve-stock', [BatchController::class, 'reserveStock'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/unreserve-stock', [BatchController::class, 'unreserveStock'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/sell-stock', [BatchController::class, 'sellStock'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/return-stock', [BatchController::class, 'returnStock'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/mark-damaged', [BatchController::class, 'markAsDamaged'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/move-location', [BatchController::class, 'moveToLocation'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/approve-quality', [BatchController::class, 'approveQuality'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/reject-quality', [BatchController::class, 'rejectQuality'])->middleware('can:inventory.batches.update');
        Route::post('/{batch}/quarantine', [BatchController::class, 'quarantineBatch'])->middleware('can:inventory.batches.update');
        Route::get('/stats/overview', [BatchController::class, 'getStats'])->middleware('can:inventory.batches.view');
        Route::get('/expiring', [BatchController::class, 'getExpiringBatches'])->middleware('can:inventory.batches.view');
        Route::get('/best-before', [BatchController::class, 'getBestBeforeBatches'])->middleware('can:inventory.batches.view');
        Route::post('/generate-next', [BatchController::class, 'generateNextBatchNumber'])->middleware('can:inventory.batches.create');
        Route::post('/check-exists', [BatchController::class, 'checkBatchNumber'])->middleware('can:inventory.batches.view');
        Route::get('/by-product', [BatchController::class, 'getByProduct'])->middleware('can:inventory.batches.view');
        Route::post('/transfer', [BatchController::class, 'transferBatches'])->middleware('can:inventory.batches.update');
        Route::post('/bulk-import', [BatchController::class, 'bulkImport'])->middleware('can:inventory.batches.create');
        Route::post('/update-statuses', [BatchController::class, 'updateBatchStatuses'])->middleware('can:inventory.batches.update');
        Route::get('/types', [BatchController::class, 'getTypes'])->middleware('can:inventory.batches.view');
    });

    // Ecommerce Order Processing
    Route::prefix('ecommerce-orders')->group(function () {
        Route::get('/', [EcommerceOrderManagementController::class, 'index'])->middleware('can:inventory.items.view');
        Route::get('/{id}', [EcommerceOrderManagementController::class, 'show'])->middleware('can:inventory.items.view');
        Route::put('/{id}/status', [EcommerceOrderManagementController::class, 'updateStatus'])->middleware('can:inventory.items.update');
    });

    // Ecommerce Deliveries
    Route::prefix('ecommerce-deliveries')->group(function () {
        Route::get('/drivers', [EcommerceDeliveryController::class, 'drivers'])->middleware('can:inventory.items.view');
        Route::get('/', [EcommerceDeliveryController::class, 'index'])->middleware('can:inventory.items.view');
        Route::get('/{id}', [EcommerceDeliveryController::class, 'show'])->middleware('can:inventory.items.view');
        Route::put('/{id}/status', [EcommerceDeliveryController::class, 'updateStatus'])->middleware('can:inventory.items.update');
        Route::post('/{id}/assign-driver', [EcommerceDeliveryController::class, 'assignDriver'])->middleware('can:inventory.items.update');
        Route::post('/{id}/proof', [EcommerceDeliveryController::class, 'uploadProof'])->middleware('can:inventory.items.update');
        Route::get('/{id}/logs', [EcommerceDeliveryController::class, 'logs'])->middleware('can:inventory.items.view');
        Route::post('/{id}/logs', [EcommerceDeliveryController::class, 'addLog'])->middleware('can:inventory.items.update');
    });

    // Delivery Vehicles
    Route::prefix('delivery-vehicles')->group(function () {
        Route::get('/', [EcommerceDeliveryVehicleController::class, 'index'])->middleware('can:inventory.items.view');
        Route::post('/', [EcommerceDeliveryVehicleController::class, 'store'])->middleware('can:inventory.items.create');
        Route::get('/{id}', [EcommerceDeliveryVehicleController::class, 'show'])->middleware('can:inventory.items.view');
        Route::put('/{id}', [EcommerceDeliveryVehicleController::class, 'update'])->middleware('can:inventory.items.update');
    });
});
