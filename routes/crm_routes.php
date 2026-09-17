<?php

use App\Http\Controllers\Api\CRM\ChatController;
use App\Http\Controllers\Api\CRM\CrmDashboardController;
use App\Http\Controllers\Api\CRM\CrmLeadController;
use App\Http\Controllers\Api\CRM\ReturnController;
use App\Http\Controllers\Api\CRM\ReviewController;
use App\Http\Controllers\Api\CRM\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->group(function () {
    Route::get('/dashboard/payment-analytics', [CrmDashboardController::class, 'paymentAnalytics'])
        ->middleware('can:crm.view');

    Route::prefix('leads')->group(function () {
        Route::get('/', [CrmLeadController::class, 'leads'])->middleware('can:crm.leads.view');
        Route::post('/', [CrmLeadController::class, 'storeLead'])->middleware('can:crm.leads.manage');
        Route::put('/{id}', [CrmLeadController::class, 'updateLead'])->middleware('can:crm.leads.manage');
        Route::post('/{id}/stage', [CrmLeadController::class, 'changeStage'])->middleware('can:crm.leads.manage');
        Route::get('/{id}/activities', [CrmLeadController::class, 'activities'])->middleware('can:crm.leads.view');
        Route::post('/{id}/activities', [CrmLeadController::class, 'addActivity'])->middleware('can:crm.leads.manage');
    });

    Route::prefix('chats')->group(function () {
        Route::get('/threads', [ChatController::class, 'threads'])->middleware('can:crm.chats.view');
        Route::get('/threads/{id}/messages', [ChatController::class, 'messages'])->middleware('can:crm.chats.view');
        Route::post('/threads/{id}/messages', [ChatController::class, 'sendMessage'])->middleware('can:crm.chats.manage');
        Route::put('/threads/{id}/messages/{messageId}', [ChatController::class, 'updateMessage'])->middleware('can:crm.chats.manage');
        Route::delete('/threads/{id}/messages/{messageId}', [ChatController::class, 'unsendMessage'])->middleware('can:crm.chats.manage');
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->middleware('can:crm.reviews.view');
        Route::get('/{review}', [ReviewController::class, 'show'])->middleware('can:crm.reviews.view');
        Route::put('/{review}/reply', [ReviewController::class, 'reply'])->middleware('can:crm.reviews.manage');
    });

    Route::prefix('vouchers')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->middleware('can:crm.vouchers.view');
        Route::post('/', [VoucherController::class, 'store'])->middleware('can:crm.vouchers.manage');
        Route::get('/{voucher}', [VoucherController::class, 'show'])->middleware('can:crm.vouchers.view');
        Route::put('/{voucher}', [VoucherController::class, 'update'])->middleware('can:crm.vouchers.manage');
    });

    Route::prefix('returns')->group(function () {
        Route::get('/', [ReturnController::class, 'index'])->middleware('can:crm.returns.view');
        Route::get('/investigation-assignees', [ReturnController::class, 'investigationAssignees'])->middleware('can:crm.returns.manage');
        Route::post('/{id}/investigation-ticket', [ReturnController::class, 'createInvestigationTicket'])->middleware('can:crm.returns.manage');
        Route::get('/{id}', [ReturnController::class, 'show'])->middleware('can:crm.returns.view');
        Route::put('/{return}/status', [ReturnController::class, 'updateStatus'])->middleware('can:crm.returns.manage');
        Route::post('/{return}/pickup', [ReturnController::class, 'createPickup'])->middleware('can:crm.returns.manage');
        Route::post('/{return}/receive', [ReturnController::class, 'receive'])->middleware('can:crm.returns.manage');
        Route::post('/{return}/refund', [ReturnController::class, 'refund'])->middleware('can:crm.returns.manage');
    });
});
