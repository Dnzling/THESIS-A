<?php

use App\Http\Controllers\Api\Store\BranchExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('store-module/expenses')->controller(BranchExpenseController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/finance/{financeExpenseId}/receipt', 'financeReceipt')->whereNumber('financeExpenseId');
    Route::get('/{id}/attachments/{index}', 'attachment')->whereNumber(['id', 'index']);
    Route::get('/{id}/receipt', 'receipt')->whereNumber('id');
    Route::get('/{id}', 'show')->whereNumber('id');
    Route::post('/{id}/approve', 'approve')->whereNumber('id');
    Route::post('/{id}/reject', 'reject')->whereNumber('id');
    Route::post('/{id}/submit-actual', 'submitActual')->whereNumber('id');
});
