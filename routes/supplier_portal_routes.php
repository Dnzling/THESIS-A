<?php
// backend/routes/supplier_portal_routes.php

use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierPortalController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierVerificationController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierRFQFeedbackController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierPOFeedbackController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierShipmentController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierDeliveryLogController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierDeliveryTemplateController;
use App\Http\Controllers\Api\Procurement\Supplier\SupplierContractController;
use Illuminate\Support\Facades\Route;

// ============================================
// SUPPLIER PORTAL ROUTES
// ============================================

Route::prefix('supplier-portal')->group(function () {
    // Unauthenticated routes
    Route::post('/register', [SupplierPortalController::class, 'register']);

    // Authenticated supplier routes
    Route::middleware(['auth:sanctum', 'account.operational'])->group(function () {
        // Supplier Portal Management
        Route::get('/my-portal', [SupplierPortalController::class, 'getMyPortal']);
        Route::put('/coordinates', [SupplierPortalController::class, 'updateCoordinates']);
        Route::put('/payment-account', [SupplierPortalController::class, 'updatePaymentAccount']);
        Route::get('/stats', [SupplierPortalController::class, 'getStats']);
        Route::get('/stores/linked', [SupplierPortalController::class, 'getLinkedStores']);
        Route::get('/stores/search', [SupplierPortalController::class, 'searchStores']);
        Route::post('/stores/link', [SupplierPortalController::class, 'linkStore']);
        Route::get('/stores/{storeId}', [SupplierPortalController::class, 'getLinkedStoreDetail'])->whereNumber('storeId');
        Route::post('/contracts/{id}/report', [SupplierContractController::class, 'report'])->whereNumber('id');
        Route::post('/contracts/{id}/terminate-request', [SupplierContractController::class, 'requestTermination'])->whereNumber('id');
        Route::post('/contracts/{id}/terminate-request/respond', [SupplierContractController::class, 'respondTerminationRequest'])->whereNumber('id');

        // Document Management
        Route::post('/documents', [SupplierPortalController::class, 'uploadDocument']);
        Route::get('/my-documents', [SupplierPortalController::class, 'getMyDocuments']);
        Route::get('/documents/{id}/download', [SupplierPortalController::class, 'downloadDocument']);

        // RFQ Feedback
        Route::get('/rfqs', [SupplierRFQFeedbackController::class, 'getRFQs']);
        Route::get('/rfqs/{id}', [SupplierRFQFeedbackController::class, 'getRFQDetail']);
        Route::post('/rfq-feedbacks', [SupplierRFQFeedbackController::class, 'submitFeedback']);
        Route::get('/rfq-feedbacks', [SupplierRFQFeedbackController::class, 'getMyFeedbacks']);
        Route::post('/rfq-negotiations/{id}/accept', [SupplierRFQFeedbackController::class, 'acceptNegotiation']);
        Route::post('/rfq-negotiations/{id}/reject', [SupplierRFQFeedbackController::class, 'rejectNegotiation']);

        // PO Feedback
        Route::get('/pos', [SupplierPOFeedbackController::class, 'getPOs']);
        Route::get('/pos/{id}', [SupplierPOFeedbackController::class, 'getPODetail']);
        Route::post('/po-feedbacks', [SupplierPOFeedbackController::class, 'submitFeedback']);
        Route::post('/po-feedbacks/{id}/confirm-receipt', [SupplierPOFeedbackController::class, 'confirmReceipt']);
        Route::get('/po-feedbacks', [SupplierPOFeedbackController::class, 'getMyFeedbacks']);

        // PO Shipments (Delivery Form)
        Route::get('/po-shipments/{poId}', [SupplierShipmentController::class, 'show'])->whereNumber('poId');
        Route::get('/shipments', [SupplierShipmentController::class, 'index']);
        Route::get('/shipments/{id}', [SupplierShipmentController::class, 'showById'])->whereNumber('id');
        Route::post('/po-shipments', [SupplierShipmentController::class, 'store']);
        Route::post('/shipments/{id}/deliver', [SupplierShipmentController::class, 'deliver'])->whereNumber('id');

        // Delivery Form Templates
        Route::get('/delivery-templates', [SupplierDeliveryTemplateController::class, 'index']);
        Route::post('/delivery-templates', [SupplierDeliveryTemplateController::class, 'store']);
        Route::put('/delivery-templates/{id}', [SupplierDeliveryTemplateController::class, 'update']);
        Route::delete('/delivery-templates/{id}', [SupplierDeliveryTemplateController::class, 'destroy']);
        Route::get('/shipments/{id}/logs', [SupplierDeliveryLogController::class, 'index']);
        Route::post('/shipments/{id}/logs', [SupplierDeliveryLogController::class, 'store']);
    });
});

// ============================================
// SUPPLIER VERIFICATION ROUTES (Admin Only)
// ============================================

Route::prefix('supplier-verifications')->middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    Route::get('/pending', [SupplierVerificationController::class, 'getPending']);
    Route::get('/', [SupplierVerificationController::class, 'index']);
    Route::get('/{id}', [SupplierVerificationController::class, 'show']);
    Route::post('/{id}/approve', [SupplierVerificationController::class, 'approve']);
    Route::post('/{id}/reject', [SupplierVerificationController::class, 'reject']);
    Route::post('/documents/{id}/review', [SupplierVerificationController::class, 'reviewDocument']);
    Route::get('/documents/{id}/download', [SupplierVerificationController::class, 'downloadDocument']);
    Route::post('/documents/{id}/auto-validate', [SupplierVerificationController::class, 'autoValidateDocument']);
    Route::post('/{id}/auto-validate-all', [SupplierVerificationController::class, 'autoValidateAllDocuments']);
});
