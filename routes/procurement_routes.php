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
    });

    // Dedicated Budget endpoints
    Route::prefix('budgets')->group(function () {
        Route::get('/summary', [BudgetController::class, 'summary']);
    });

    // Suppliers
    Route::prefix('suppliers')->middleware('can:procurement.suppliers.view')->group(function () {
        Route::get('/stats', [ProcurementDashboardController::class, 'getStats']);
        Route::get('/summary-cards', [ProcurementDashboardController::class, 'getSummaryCards']);
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::post('/', [SupplierController::class, 'store'])->middleware('can:procurement.suppliers.manage');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('can:procurement.suppliers.manage');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('can:procurement.suppliers.manage');
        Route::get('/{id}/products', [SupplierController::class, 'products']);
        Route::post('/{id}/products', [SupplierController::class, 'attachProducts'])->middleware('can:procurement.suppliers.manage');
        Route::get('/{id}/performance', [SupplierController::class, 'performance']);
        Route::get('/{id}/delivery-history', [PurchaseOrderPrintEmailController::class, 'getSupplierDeliveryHistory']);
        Route::post('/{id}/update-rating', [SupplierController::class, 'updateRating'])->middleware('can:procurement.suppliers.manage');
    });

    // Supplier Contracts
    Route::prefix('supplier-contracts')->middleware('can:procurement.supplier_contracts.view')->group(function () {
        Route::get('/', [SupplierContractController::class, 'index']);
        Route::get('/{id}', [SupplierContractController::class, 'show']);
        Route::post('/', [SupplierContractController::class, 'store'])->middleware('can:procurement.supplier_contracts.manage');
        Route::put('/{id}', [SupplierContractController::class, 'update'])->middleware('can:procurement.supplier_contracts.manage');
        Route::delete('/{id}', [SupplierContractController::class, 'destroy'])->middleware('can:procurement.supplier_contracts.manage');
        Route::post('/{id}/activate', [SupplierContractController::class, 'activate'])->middleware('can:procurement.supplier_contracts.approve');
        Route::post('/{id}/terminate', [SupplierContractController::class, 'terminate'])->middleware('can:procurement.supplier_contracts.approve');
        Route::get('/expiring', [SupplierContractController::class, 'expiring']);
    });

    // Purchase Requisitions (Procurement module)
    // Inventory has its own "stock-order-requests" endpoints under /api/inventory.
    Route::prefix('requisitions')->middleware('can:procurement.requisitions.view')->group(function () {
        // Branch-scoped inventory for procurement requisition UI (RBAC: procurement.requisitions.view)
        Route::get('/branch/{branchId}/inventory', [BranchInventoryController::class, 'index']);

        Route::get('/', [PurchaseRequisitionController::class, 'index']);
        Route::get('/{id}', [PurchaseRequisitionController::class, 'show']);
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->middleware('can:procurement.requisitions.manage');
        Route::put('/{id}', [PurchaseRequisitionController::class, 'update'])->middleware('can:procurement.requisitions.manage');
        Route::delete('/{id}', [PurchaseRequisitionController::class, 'destroy'])->middleware('can:procurement.requisitions.manage');
        Route::post('/{id}/submit', [PurchaseRequisitionController::class, 'submit'])->middleware('can:procurement.requisitions.manage');
        Route::post('/{id}/approve', [PurchaseRequisitionController::class, 'approve'])->middleware('can:procurement.requisitions.approve');
        Route::post('/{id}/reject', [PurchaseRequisitionController::class, 'reject'])->middleware('can:procurement.requisitions.approve');
        Route::post('/{id}/cancel', [PurchaseRequisitionController::class, 'cancel'])->middleware('can:procurement.requisitions.manage');
        Route::get('/{id}/delivery-logs', [PurchaseRequisitionController::class, 'deliveryLogs']);
    });

    // Stock Order Requests (from Branch Inventory low stock)
    Route::prefix('stock-order-requests')->middleware('can:procurement.stock_order_requests.view')->group(function () {
        Route::get('/', [StockOrderRequestController::class, 'index']);
        Route::post('/', [StockOrderRequestController::class, 'store'])->middleware('can:procurement.stock_order_requests.manage');
        Route::put('/{id}', [StockOrderRequestController::class, 'update'])->middleware('can:procurement.stock_order_requests.manage');
        // Named routes MUST come before wildcard {id} routes
        Route::post('/bulk/create-from-low-stock', [StockOrderRequestController::class, 'createFromLowStock'])->middleware('can:procurement.stock_order_requests.manage');
        Route::get('/pending/for-conversion', [StockOrderRequestController::class, 'pendingForConversion']);
        Route::get('/summary', [StockOrderRequestController::class, 'summary']);
        // Wildcard routes last
        Route::get('/{id}', [StockOrderRequestController::class, 'show']);
        Route::post('/{id}/approve', [StockOrderRequestController::class, 'approve'])->middleware('can:procurement.stock_order_requests.approve');
        Route::post('/{id}/reject', [StockOrderRequestController::class, 'reject'])->middleware('can:procurement.stock_order_requests.approve');
    });

    // Request for Quotations (RFQ)
    Route::prefix('rfqs')->middleware('can:procurement.rfq.view')->group(function () {
        Route::get('/', [RequestForQuotationController::class, 'index']);
        Route::get('/{id}', [RequestForQuotationController::class, 'show']);
        Route::post('/create-from-requisition-split', [RequestForQuotationController::class, 'createFromRequisitionSplit'])->middleware('can:procurement.rfq.manage');
        Route::post('/', [RequestForQuotationController::class, 'store'])->middleware('can:procurement.rfq.manage');
        Route::put('/{id}', [RequestForQuotationController::class, 'update'])->middleware('can:procurement.rfq.manage');
        Route::delete('/{id}', [RequestForQuotationController::class, 'destroy'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/send', [RequestForQuotationController::class, 'send'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/close', [RequestForQuotationController::class, 'close'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/award', [RequestForQuotationController::class, 'award'])->middleware('can:procurement.rfq.approve');
        Route::post('/{id}/cancel', [RequestForQuotationController::class, 'cancel'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/portal-feedbacks/{feedbackId}/review', [RequestForQuotationController::class, 'reviewPortalFeedback'])->middleware('can:procurement.rfq.approve');
        Route::post('/{id}/portal-feedbacks/{feedbackId}/negotiate', [RequestForQuotationController::class, 'negotiatePortalFeedback'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/portal-feedbacks/bulk-approve', [RequestForQuotationController::class, 'bulkApprovePortalFeedbacks'])->middleware('can:procurement.rfq.approve');

        // Compare quotations
        Route::get('/{rfqId}/quotations/compare', [SupplierQuotationController::class, 'compare']);
    });

    // Supplier Quotations
    Route::prefix('quotations')->middleware('can:procurement.rfq.view')->group(function () {
        Route::get('/', [SupplierQuotationController::class, 'index']);
        Route::get('/{id}', [SupplierQuotationController::class, 'show']);
        Route::post('/', [SupplierQuotationController::class, 'store'])->middleware('can:procurement.rfq.manage');
        Route::post('/{id}/evaluate', [SupplierQuotationController::class, 'evaluate'])->middleware('can:procurement.rfq.approve');
        Route::post('/{id}/accept', [SupplierQuotationController::class, 'accept'])->middleware('can:procurement.rfq.approve');
        Route::post('/{id}/reject', [SupplierQuotationController::class, 'reject'])->middleware('can:procurement.rfq.approve');
    });

    // Purchase Orders
    Route::prefix('purchase-orders')->middleware('can:procurement.purchase_orders.view')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::get('/approved', [PurchaseOrderPrintEmailController::class, 'getApprovedOrders']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('can:procurement.purchase_orders.manage');
        Route::put('/{id}', [PurchaseOrderController::class, 'update'])->middleware('can:procurement.purchase_orders.manage');
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->middleware('can:procurement.purchase_orders.manage');
        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:procurement.purchase_orders.approve');
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject'])->middleware('can:procurement.purchase_orders.approve');
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send'])->middleware('can:procurement.purchase_orders.manage');
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:procurement.purchase_orders.manage');
        Route::get('/{id}/print', [PurchaseOrderPrintEmailController::class, 'generatePdf']);
        Route::post('/{id}/email', [PurchaseOrderPrintEmailController::class, 'emailPo'])->middleware('can:procurement.purchase_orders.manage');
        Route::get('/{id}/label', [PurchaseOrderPrintEmailController::class, 'generateLabel']);
        Route::post('/{id}/request-revision', [PurchaseOrderPrintEmailController::class, 'requestRevision'])->middleware('can:procurement.purchase_orders.manage');
        Route::get('/summary', [PurchaseOrderController::class, 'summary']);
        Route::get('/{id}/delivery-logs', [PurchaseOrderController::class, 'deliveryLogs']);

        // Pending receipt
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO']);
    });

    // Goods Receipts
    Route::prefix('goods-receipts')->middleware('can:procurement.receiving.view')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index']);
        Route::get('/{id}/print', [GoodsReceiptController::class, 'print']);
        Route::get('/{id}', [GoodsReceiptController::class, 'show']);
        Route::post('/', [GoodsReceiptController::class, 'store'])->middleware('can:procurement.receiving.manage');
        Route::post('/{id}/verify', [GoodsReceiptController::class, 'verify'])->middleware('can:procurement.receiving.approve');
        Route::get('/summary', [GoodsReceiptController::class, 'summary']);
    });

    // Invoices
    Route::prefix('invoices')->middleware('can:procurement.invoices.view')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/pending/match', [InvoiceController::class, 'getPendingMatch']);
        Route::get('/exceptions', [InvoiceController::class, 'getExceptions']);
        Route::get('/{id}', [InvoiceController::class, 'show'])->whereNumber('id');
        Route::post('/', [InvoiceController::class, 'store'])->middleware('can:procurement.invoices.manage');
        Route::post('/from-grn', [InvoiceController::class, 'createFromGoodsReceipt'])->middleware('can:procurement.invoices.manage');
        Route::put('/{id}', [InvoiceController::class, 'update'])->whereNumber('id')->middleware('can:procurement.invoices.manage');
        Route::post('/{id}/match', [InvoiceController::class, 'performMatch'])->whereNumber('id')->middleware('can:procurement.invoices.approve');
        Route::post('/{id}/approve', [InvoiceController::class, 'approve'])->whereNumber('id')->middleware('can:procurement.invoices.approve');
        Route::post('/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->whereNumber('id')->middleware('can:procurement.invoices.approve');
        Route::post('/{id}/schedule-payment', [InvoiceController::class, 'schedulePayment'])->whereNumber('id')->middleware('can:procurement.invoices.manage');
    });

    // Supplier Payments
    Route::prefix('payments')->group(function () {
        // View endpoints (permit finance/payables users too)
        Route::get('/', [SupplierPaymentController::class, 'index'])->middleware('module:procurement');
        Route::get('/pending', [SupplierPaymentController::class, 'pending'])->middleware('module:procurement');
        Route::get('/summary', [SupplierPaymentController::class, 'summary'])->middleware('module:procurement');

        // Mutations keep procurement permissions
        Route::post('/', [SupplierPaymentController::class, 'store'])->middleware('can:procurement.payments.manage');
        Route::get('/{id}', [SupplierPaymentController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}', [SupplierPaymentController::class, 'destroy'])->whereNumber('id')->middleware('can:procurement.payments.manage');
        Route::post('/{id}/approve', [SupplierPaymentController::class, 'approve'])->whereNumber('id')->middleware('can:procurement.payments.approve');
        Route::post('/{id}/process', [SupplierPaymentController::class, 'process'])->whereNumber('id')->middleware('can:procurement.payments.approve');
        Route::post('/{id}/cancel', [SupplierPaymentController::class, 'cancel'])->whereNumber('id')->middleware('can:procurement.payments.manage');
    });

    // Procurement Settings
    Route::prefix('settings')->middleware('can:procurement.settings.manage')->group(function () {
        Route::get('/', [ProcurementSettingsController::class, 'show']);
        Route::put('/', [ProcurementSettingsController::class, 'update']);
        Route::get('/presets', [ProcurementSettingsController::class, 'presets']);
        Route::post('/apply-preset', [ProcurementSettingsController::class, 'applyPreset']);
        Route::get('/default-tiers', [ProcurementSettingsController::class, 'defaultTiers']);
        Route::post('/test-rfq', [ProcurementSettingsController::class, 'testRfq']);
        Route::post('/calculate-transfer-cost', [ProcurementSettingsController::class, 'calculateTransferCost']);
    });

    // Role Approval Limits
    Route::prefix('role-limits')->middleware('can:procurement.role_limits.view')->group(function () {
        Route::get('/', [RoleApprovalLimitController::class, 'index']);
        Route::get('/{id}', [RoleApprovalLimitController::class, 'show']);
        Route::post('/', [RoleApprovalLimitController::class, 'store'])->middleware('can:procurement.role_limits.manage');
        Route::put('/{id}', [RoleApprovalLimitController::class, 'update'])->middleware('can:procurement.role_limits.manage');
        Route::delete('/{id}', [RoleApprovalLimitController::class, 'destroy'])->middleware('can:procurement.role_limits.manage');
        Route::get('/role/{roleId}', [RoleApprovalLimitController::class, 'getByRole']);
        Route::post('/check', [RoleApprovalLimitController::class, 'checkApproval']);
    });

    // Procurement Inventory Management
    Route::prefix('inventory')->middleware('can:procurement.inventory.view')->group(function () {
        Route::get('/', [ProcurementInventoryController::class, 'index']);
        Route::get('/summary', [ProcurementInventoryController::class, 'summary']);
        Route::get('/low-stock', [ProcurementInventoryController::class, 'lowStock']);
        Route::get('/{id}', [ProcurementInventoryController::class, 'show']);
        Route::post('/init', [ProcurementInventoryController::class, 'initialize'])->middleware('can:procurement.inventory.manage');
        Route::put('/{id}', [ProcurementInventoryController::class, 'update'])->middleware('can:procurement.inventory.manage');
    });

    // Products (automation features)
    Route::prefix('product-inventory')->middleware('can:procurement.products.view')->group(function () {
        Route::get('/', [BranchInventoryController::class, 'index']);
        Route::get('/history', [PurchaseOrderPrintEmailController::class, 'getProductHistory']);
        Route::get('/{id}', [BranchInventoryController::class, 'show']);
        Route::get('/{productId}/alternative-suppliers', [PurchaseOrderPrintEmailController::class, 'getAlternativeSuppliers']);
    });

    Route::prefix('products')->middleware('can:procurement.products.view')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store'])->middleware('can:procurement.products.manage');
        Route::put('/{id}', [ProductController::class, 'update'])->middleware('can:procurement.products.manage');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('can:procurement.products.manage');
    });

    // Branches (budget checking)
    Route::prefix('branches')->middleware('can:procurement.purchase_orders.view')->group(function () {
        Route::get('/{branchId}/budget', [PurchaseOrderPrintEmailController::class, 'getBranchBudget']);
    });
});
