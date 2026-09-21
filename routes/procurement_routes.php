<?php
// backend/routes/api.php

use App\Http\Controllers\Api\Inventory\BranchInventoryController;
use Illuminate\Support\Facades\Route;


// ============================================
// PROCUREMENT ROUTES
// ============================================
use App\Http\Controllers\Api\Procurement\Supplier\SupplierController;
use App\Http\Controllers\Api\Procurement\Supplier\SupplierContractController;
use App\Http\Controllers\Api\Procurement\Supplier\SupplierPaymentController;
use App\Http\Controllers\Api\Procurement\Requisition\PurchaseRequisitionController;
use App\Http\Controllers\Api\Procurement\RFQ\RequestForQuotationController;
use App\Http\Controllers\Api\Procurement\RFQ\SupplierQuotationController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderPrintEmailController;
use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController;
use App\Http\Controllers\Api\Procurement\InvoiceController;
use App\Http\Controllers\Api\Procurement\Config\ProcurementSettingsController;
use App\Http\Controllers\Api\Procurement\Config\RoleApprovalLimitController;
use App\Http\Controllers\Api\Procurement\DashboardController as ProcurementDashboardController;
use App\Http\Controllers\Api\Procurement\Inventory\ProcurementInventoryController;
use App\Http\Controllers\Api\Procurement\StockOrder\StockOrderRequestController;
use App\Http\Controllers\Api\ProductCatalog\ProductController;
use App\Http\Controllers\Api\Procurement\ProductController as ProcurementProductController;
use App\Http\Controllers\Api\Procurement\AnalyticsController;
use App\Http\Controllers\Api\Procurement\BudgetController;

// ============================================
// PROCUREMENT MANAGEMENT ROUTES
// ============================================
Route::prefix('procurement')->group(function () {
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'getDashboard']);
        Route::get('/reorder-suggestions', [AnalyticsController::class, 'getReorderSuggestions']);
        Route::get('/spend', [AnalyticsController::class, 'getSpendAnalytics']);
        Route::get('/supplier-performance', [AnalyticsController::class, 'getSupplierPerformance']);
        Route::get('/receiving-accuracy', [AnalyticsController::class, 'getReceivingAccuracy']);
        Route::get('/budget', [AnalyticsController::class, 'getBudgetTracking']);
        Route::get('/lead-time', [AnalyticsController::class, 'getLeadTimeAnalysis']);
        Route::get('/forecasting', [AnalyticsController::class, 'getForecasting']);
    });

    // Dedicated Budget endpoints
    Route::prefix('budgets')->group(function () {
        Route::get('/summary', [BudgetController::class, 'summary']);
    });

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/stats', [ProcurementDashboardController::class, 'getStats']);
        Route::get('/summary-cards', [ProcurementDashboardController::class, 'getSummaryCards']);
        Route::get('/verified-directory', [SupplierController::class, 'verifiedDirectory']);
        Route::get('/verified-directory/{portalId}', [SupplierController::class, 'verifiedDirectoryShow']);
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        Route::get('/{id}/products', [SupplierController::class, 'products']);
        Route::post('/{id}/products', [SupplierController::class, 'attachProducts']);
        Route::get('/{id}/performance', [SupplierController::class, 'performance']);
        Route::get('/{id}/delivery-history', [PurchaseOrderPrintEmailController::class, 'getSupplierDeliveryHistory']);
        Route::post('/{id}/update-rating', [SupplierController::class, 'updateRating']);
    });

    // Supplier Contracts
    Route::prefix('supplier-contracts')->group(function () {
        Route::get('/', [SupplierContractController::class, 'index']);
        Route::get('/{id}', [SupplierContractController::class, 'show']);
        Route::post('/', [SupplierContractController::class, 'store']);
        Route::put('/{id}', [SupplierContractController::class, 'update']);
        Route::delete('/{id}', [SupplierContractController::class, 'destroy']);
        Route::post('/{id}/activate', [SupplierContractController::class, 'activate']);
        Route::post('/{id}/reject', [SupplierContractController::class, 'reject']);
        Route::post('/{id}/terminate', [SupplierContractController::class, 'terminate']);
        Route::post('/{id}/terminate-request', [SupplierContractController::class, 'requestTermination']);
        Route::post('/{id}/terminate-request/respond', [SupplierContractController::class, 'respondTerminationRequest']);
        Route::post('/{id}/report', [SupplierContractController::class, 'report']);
        Route::get('/expiring', [SupplierContractController::class, 'expiring']);
    });

    // Purchase Requisitions (Procurement module)
    // Inventory has its own "stock-order-requests" endpoints under /api/inventory.
    // Purchase order creation needs branch inventory even when the user does
    // not have the requisition-view permission.
    Route::get('/requisitions/branch/{branchId}/inventory', [BranchInventoryController::class, 'index']);
    Route::prefix('requisitions')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index']);
        Route::get('/{id}', [PurchaseRequisitionController::class, 'show']);
        Route::post('/', [PurchaseRequisitionController::class, 'store']);
        Route::put('/{id}', [PurchaseRequisitionController::class, 'update']);
        Route::delete('/{id}', [PurchaseRequisitionController::class, 'destroy']);
        Route::post('/{id}/submit', [PurchaseRequisitionController::class, 'submit']);
        Route::post('/{id}/start-processing', [PurchaseRequisitionController::class, 'startProcessing']);
        Route::post('/{id}/approve', [PurchaseRequisitionController::class, 'approve']);
        Route::post('/{id}/reject', [PurchaseRequisitionController::class, 'reject']);
        Route::post('/{id}/cancel', [PurchaseRequisitionController::class, 'cancel']);
        Route::get('/{id}/delivery-logs', [PurchaseRequisitionController::class, 'deliveryLogs']);
    });

    // Stock Order Requests (from Branch Inventory low stock)
    Route::prefix('stock-order-requests')->group(function () {
        Route::get('/', [StockOrderRequestController::class, 'index']);
        Route::post('/', [StockOrderRequestController::class, 'store']);
        Route::put('/{id}', [StockOrderRequestController::class, 'update']);
        // Named routes MUST come before wildcard {id} routes
        Route::post('/bulk/create-from-low-stock', [StockOrderRequestController::class, 'createFromLowStock']);
        Route::get('/pending/for-conversion', [StockOrderRequestController::class, 'pendingForConversion']);
        Route::get('/summary', [StockOrderRequestController::class, 'summary']);
        // Wildcard routes last
        Route::get('/{id}', [StockOrderRequestController::class, 'show']);
        Route::post('/{id}/approve', [StockOrderRequestController::class, 'approve']);
        Route::post('/{id}/reject', [StockOrderRequestController::class, 'reject']);
    });

    // Request for Quotations (RFQ)
    Route::prefix('rfqs')->group(function () {
        Route::get('/', [RequestForQuotationController::class, 'index']);
        Route::get('/{id}', [RequestForQuotationController::class, 'show']);
        Route::post('/create-from-requisition-split', [RequestForQuotationController::class, 'createFromRequisitionSplit']);
        Route::post('/', [RequestForQuotationController::class, 'store']);
        Route::put('/{id}', [RequestForQuotationController::class, 'update']);
        Route::delete('/{id}', [RequestForQuotationController::class, 'destroy']);
        Route::post('/{id}/send', [RequestForQuotationController::class, 'send']);
        Route::post('/{id}/close', [RequestForQuotationController::class, 'close']);
        Route::post('/{id}/award', [RequestForQuotationController::class, 'award']);
        Route::post('/{id}/cancel', [RequestForQuotationController::class, 'cancel']);
        Route::post('/{id}/portal-feedbacks/{feedbackId}/review', [RequestForQuotationController::class, 'reviewPortalFeedback']);
        Route::post('/{id}/portal-feedbacks/{feedbackId}/negotiate', [RequestForQuotationController::class, 'negotiatePortalFeedback']);
        Route::post('/{id}/portal-feedbacks/bulk-approve', [RequestForQuotationController::class, 'bulkApprovePortalFeedbacks']);

        // Compare quotations
        Route::get('/{rfqId}/quotations/compare', [SupplierQuotationController::class, 'compare']);
    });

    // Supplier Quotations
    Route::prefix('quotations')->group(function () {
        Route::get('/', [SupplierQuotationController::class, 'index']);
        Route::get('/{id}', [SupplierQuotationController::class, 'show']);
        Route::post('/', [SupplierQuotationController::class, 'store']);
        Route::post('/{id}/evaluate', [SupplierQuotationController::class, 'evaluate']);
        Route::post('/{id}/accept', [SupplierQuotationController::class, 'accept']);
        Route::post('/{id}/reject', [SupplierQuotationController::class, 'reject']);
    });

    // Purchase Orders
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/shipping-estimate', [PurchaseOrderController::class, 'estimateShippingFee']);
        Route::get('/approved', [PurchaseOrderPrintEmailController::class, 'getApprovedOrders']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::get('/{id}/pickup-vehicles', [PurchaseOrderController::class, 'pickupVehicles']);
        Route::get('/{id}/pickup-drivers', [PurchaseOrderController::class, 'pickupDrivers']);
        Route::post('/{id}/pickup', [PurchaseOrderController::class, 'assignPickup']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::put('/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject']);
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send']);
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::get('/{id}/print', [PurchaseOrderPrintEmailController::class, 'generatePdf']);
        Route::post('/{id}/email', [PurchaseOrderPrintEmailController::class, 'emailPo']);
        Route::get('/{id}/label', [PurchaseOrderPrintEmailController::class, 'generateLabel']);
        Route::post('/{id}/request-revision', [PurchaseOrderPrintEmailController::class, 'requestRevision']);
        Route::get('/summary', [PurchaseOrderController::class, 'summary']);
        Route::get('/{id}/delivery-logs', [PurchaseOrderController::class, 'deliveryLogs']);

        // Pending receipt
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO']);
    });

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/pending/match', [InvoiceController::class, 'getPendingMatch']);
        Route::get('/exceptions', [InvoiceController::class, 'getExceptions']);
        Route::get('/{id}', [InvoiceController::class, 'show'])->whereNumber('id');
        Route::post('/', [InvoiceController::class, 'store']);
        Route::post('/from-grn', [InvoiceController::class, 'createFromGoodsReceipt']);
        Route::put('/{id}', [InvoiceController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/match', [InvoiceController::class, 'performMatch'])->whereNumber('id');
        Route::post('/{id}/approve', [InvoiceController::class, 'approve'])->whereNumber('id');
        Route::post('/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->whereNumber('id');
        Route::post('/{id}/schedule-payment', [InvoiceController::class, 'schedulePayment'])->whereNumber('id');
    });

    // Supplier Payments
    Route::prefix('payments')->group(function () {
        // View endpoints (permit finance/payables users too)
        Route::get('/', [SupplierPaymentController::class, 'index'])->middleware('module:procurement');
        Route::get('/pending', [SupplierPaymentController::class, 'pending'])->middleware('module:procurement');
        Route::get('/summary', [SupplierPaymentController::class, 'summary'])->middleware('module:procurement');

        // Mutations keep procurement permissions
        Route::post('/', [SupplierPaymentController::class, 'store']);
        Route::get('/{id}', [SupplierPaymentController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}', [SupplierPaymentController::class, 'destroy'])->whereNumber('id');
        Route::post('/{id}/approve', [SupplierPaymentController::class, 'approve'])->whereNumber('id');
        Route::post('/{id}/process', [SupplierPaymentController::class, 'process'])->whereNumber('id');
        Route::post('/{id}/cancel', [SupplierPaymentController::class, 'cancel'])->whereNumber('id');
    });

    // Procurement Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [ProcurementSettingsController::class, 'show']);
        Route::put('/', [ProcurementSettingsController::class, 'update']);
        Route::get('/presets', [ProcurementSettingsController::class, 'presets']);
        Route::post('/apply-preset', [ProcurementSettingsController::class, 'applyPreset']);
        Route::get('/default-tiers', [ProcurementSettingsController::class, 'defaultTiers']);
        Route::post('/test-rfq', [ProcurementSettingsController::class, 'testRfq']);
        Route::post('/calculate-transfer-cost', [ProcurementSettingsController::class, 'calculateTransferCost']);
    });

    // Role Approval Limits
    Route::prefix('role-limits')->group(function () {
        Route::get('/', [RoleApprovalLimitController::class, 'index']);
        Route::get('/{id}', [RoleApprovalLimitController::class, 'show']);
        Route::post('/', [RoleApprovalLimitController::class, 'store']);
        Route::put('/{id}', [RoleApprovalLimitController::class, 'update']);
        Route::delete('/{id}', [RoleApprovalLimitController::class, 'destroy']);
        Route::get('/role/{roleId}', [RoleApprovalLimitController::class, 'getByRole']);
        Route::post('/check', [RoleApprovalLimitController::class, 'checkApproval']);
    });

    // Procurement Inventory Management
    Route::prefix('inventory')->group(function () {
        Route::get('/', [ProcurementInventoryController::class, 'index']);
        Route::get('/summary', [ProcurementInventoryController::class, 'summary']);
        Route::get('/low-stock', [ProcurementInventoryController::class, 'lowStock']);
        Route::get('/{id}', [ProcurementInventoryController::class, 'show']);
        Route::post('/init', [ProcurementInventoryController::class, 'initialize']);
        Route::put('/{id}', [ProcurementInventoryController::class, 'update']);
    });

    // Products (automation features)
    Route::prefix('product-inventory')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index']);
        Route::get('/history', [PurchaseOrderPrintEmailController::class, 'getProductHistory']);
        Route::get('/{id}', [BranchInventoryController::class, 'show']);
        Route::get('/{productId}/alternative-suppliers', [PurchaseOrderPrintEmailController::class, 'getAlternativeSuppliers']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProcurementProductController::class, 'index']);
        Route::get('/{id}', [ProcurementProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Branches (budget checking)
    Route::prefix('branches')->group(function () {
        Route::get('/{branchId}/budget', [PurchaseOrderPrintEmailController::class, 'getBranchBudget']);
    });
});
