<?php
// backend/routes/api.php

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
use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController;
use App\Http\Controllers\Api\Procurement\Config\ProcurementSettingsController;
use App\Http\Controllers\Api\Procurement\Config\RoleApprovalLimitController;
use App\Http\Controllers\Api\Procurement\DashboardController as ProcurementDashboardController;


// ============================================
// PROCUREMENT MANAGEMENT ROUTES
// ============================================
Route::prefix('procurement')->group(function () {

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/stats', [ProcurementDashboardController::class, 'getStats']);
        Route::get('/summary-cards', [ProcurementDashboardController::class, 'getSummaryCards']);
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
        Route::post('/{id}/products', [SupplierController::class, 'attachProducts']);
        Route::get('/{id}/performance', [SupplierController::class, 'performance']);
        Route::post('/{id}/update-rating', [SupplierController::class, 'updateRating']);
    });

    // Supplier Contracts
    Route::prefix('contracts')->group(function () {
        Route::get('/', [SupplierContractController::class, 'index']);
        Route::get('/{id}', [SupplierContractController::class, 'show']);
        Route::post('/', [SupplierContractController::class, 'store']);
        Route::put('/{id}', [SupplierContractController::class, 'update']);
        Route::delete('/{id}', [SupplierContractController::class, 'destroy']);
        Route::post('/{id}/activate', [SupplierContractController::class, 'activate']);
        Route::post('/{id}/terminate', [SupplierContractController::class, 'terminate']);
        Route::get('/expiring', [SupplierContractController::class, 'expiring']);
    });

    // Purchase Requisitions
    Route::prefix('requisitions')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index']);
        Route::get('/{id}', [PurchaseRequisitionController::class, 'show']);
        Route::post('/', [PurchaseRequisitionController::class, 'store']);
        Route::put('/{id}', [PurchaseRequisitionController::class, 'update']);
        Route::delete('/{id}', [PurchaseRequisitionController::class, 'destroy']);
        Route::post('/{id}/submit', [PurchaseRequisitionController::class, 'submit']);
        Route::post('/{id}/approve', [PurchaseRequisitionController::class, 'approve']);
        Route::post('/{id}/reject', [PurchaseRequisitionController::class, 'reject']);
        Route::post('/{id}/cancel', [PurchaseRequisitionController::class, 'cancel']);
    });

    // Request for Quotations (RFQ)
    Route::prefix('rfqs')->group(function () {
        Route::get('/', [RequestForQuotationController::class, 'index']);
        Route::get('/{id}', [RequestForQuotationController::class, 'show']);
        Route::post('/', [RequestForQuotationController::class, 'store']);
        Route::delete('/{id}', [RequestForQuotationController::class, 'destroy']);
        Route::post('/{id}/send', [RequestForQuotationController::class, 'send']);
        Route::post('/{id}/close', [RequestForQuotationController::class, 'close']);
        Route::post('/{id}/award', [RequestForQuotationController::class, 'award']);
        Route::post('/{id}/cancel', [RequestForQuotationController::class, 'cancel']);

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
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject']);
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send']);
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::get('/summary', [PurchaseOrderController::class, 'summary']);

        // Pending receipt
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO']);
    });

    // Goods Receipts
    Route::prefix('goods-receipts')->group(function () {
        Route::get('/', [GoodsReceiptController::class, 'index']);
        Route::get('/{id}', [GoodsReceiptController::class, 'show']);
        Route::post('/', [GoodsReceiptController::class, 'store']);
        Route::post('/{id}/verify', [GoodsReceiptController::class, 'verify']);
        Route::get('/summary', [GoodsReceiptController::class, 'summary']);
    });

    // Supplier Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [SupplierPaymentController::class, 'index']);
        Route::get('/{id}', [SupplierPaymentController::class, 'show']);
        Route::post('/', [SupplierPaymentController::class, 'store']);
        Route::delete('/{id}', [SupplierPaymentController::class, 'destroy']);
        Route::post('/{id}/approve', [SupplierPaymentController::class, 'approve']);
        Route::post('/{id}/process', [SupplierPaymentController::class, 'process']);
        Route::post('/{id}/cancel', [SupplierPaymentController::class, 'cancel']);
        Route::get('/pending', [SupplierPaymentController::class, 'pending']);
        Route::get('/summary', [SupplierPaymentController::class, 'summary']);
    });

    // Procurement Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [ProcurementSettingsController::class, 'show']);
        Route::put('/', [ProcurementSettingsController::class, 'update']);
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
});
