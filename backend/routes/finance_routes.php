<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Finance\FinanceDashboardController;
use App\Http\Controllers\Api\Finance\FinanceExpenseController;
use App\Http\Controllers\Api\Finance\FinanceBudgetController;
use App\Http\Controllers\Api\Finance\FinancePayablesController;
use App\Http\Controllers\Api\Finance\FinanceReceivablesController;
use App\Http\Controllers\Api\Finance\FinancePayrollController;
use App\Http\Controllers\Api\Finance\FinanceInvoiceController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;

Route::prefix('finance')->group(function () {
    Route::get('/dashboard', [FinanceDashboardController::class, 'index']);

    Route::get('/payables', [FinancePayablesController::class, 'index']);
    Route::get('/receivables', [FinanceReceivablesController::class, 'index']);
    Route::get('/payroll', [FinancePayrollController::class, 'index']);
    Route::get('/invoices', [FinanceInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [FinanceInvoiceController::class, 'show']);
    Route::post('/invoices/{id}/match', [FinanceInvoiceController::class, 'match']);
    Route::post('/invoices/{id}/approve', [FinanceInvoiceController::class, 'approve']);
    Route::post('/invoices/{id}/mark-paid', [FinanceInvoiceController::class, 'markPaid']);

    Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
    Route::post('/purchase-orders/{id}/reject', [PurchaseOrderController::class, 'reject']);

    Route::get('/expenses', [FinanceExpenseController::class, 'index']);
    Route::post('/expenses', [FinanceExpenseController::class, 'store']);
    Route::get('/expenses/{id}', [FinanceExpenseController::class, 'show']);
    Route::put('/expenses/{id}', [FinanceExpenseController::class, 'update']);
    Route::post('/expenses/{id}/approve', [FinanceExpenseController::class, 'approve']);
    Route::post('/expenses/{id}/reject', [FinanceExpenseController::class, 'reject']);
    Route::post('/expenses/{id}/mark-paid', [FinanceExpenseController::class, 'markPaid']);
    Route::delete('/expenses/{id}', [FinanceExpenseController::class, 'destroy']);

    Route::get('/budgets', [FinanceBudgetController::class, 'index']);
    Route::post('/budgets', [FinanceBudgetController::class, 'store']);
    Route::get('/budgets/{id}', [FinanceBudgetController::class, 'show']);
    Route::put('/budgets/{id}', [FinanceBudgetController::class, 'update']);
    Route::delete('/budgets/{id}', [FinanceBudgetController::class, 'destroy']);
});
