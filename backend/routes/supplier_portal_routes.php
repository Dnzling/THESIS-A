<?php
// backend/routes/supplier_portal_routes.php

use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierPortalController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierVerificationController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierRFQFeedbackController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierPOFeedbackController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierShipmentController;
use App\Http\Controllers\Api\Procurement\SupplierPortal\SupplierDeliveryTemplateController;
use Illuminate\Support\Facades\Route;

// ============================================
// SUPPLIER PORTAL ROUTES
// ============================================

Route::prefix('supplier-portal')->group(function () {
    // Unauthenticated routes
    Route::post('/register', [SupplierPortalController::class, 'register']);

    // Authenticated supplier routes
    Route::middleware('auth:sanctum')->group(function () {
        // Supplier Portal Management
        Route::get('/my-portal', [SupplierPortalController::class, 'getMyPortal']);
        Route::get('/stats', [SupplierPortalController::class, 'getStats']);

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
        Route::post('/po-shipments', [SupplierShipmentController::class, 'store']);
        Route::get('/po-shipments/{poId}/invoice', [SupplierShipmentController::class, 'invoice'])->whereNumber('poId');

        // Delivery Form Templates
        Route::get('/delivery-templates', [SupplierDeliveryTemplateController::class, 'index']);
        Route::post('/delivery-templates', [SupplierDeliveryTemplateController::class, 'store']);
        Route::put('/delivery-templates/{id}', [SupplierDeliveryTemplateController::class, 'update']);
        Route::delete('/delivery-templates/{id}', [SupplierDeliveryTemplateController::class, 'destroy']);
    });
});

// ============================================
// SUPPLIER VERIFICATION ROUTES (Admin Only)
// ============================================

Route::prefix('supplier-verifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/pending', [SupplierVerificationController::class, 'getPending']);
    Route::get('/', [SupplierVerificationController::class, 'index']);
    Route::get('/{id}', [SupplierVerificationController::class, 'show']);
    Route::post('/{id}/approve', [SupplierVerificationController::class, 'approve']);
    Route::post('/{id}/reject', [SupplierVerificationController::class, 'reject']);
    Route::post('/documents/{id}/review', [SupplierVerificationController::class, 'reviewDocument']);
});
