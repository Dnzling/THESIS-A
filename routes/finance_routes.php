<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Finance\FinanceDashboardController;
use App\Http\Controllers\Api\Finance\FinanceExpenseController;
use App\Http\Controllers\Api\Finance\FinanceBudgetController;
use App\Http\Controllers\Api\Finance\FinanceCashflowController;
use App\Http\Controllers\Api\Finance\FinancePayablesController;
use App\Http\Controllers\Api\Finance\FinanceReceivablesController;
use App\Http\Controllers\Api\Finance\FinancePayrollController;
use App\Http\Controllers\Api\Finance\FinanceInvoiceController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderPrintEmailController;
use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController;

Route::prefix('finance')->group(function () {
    Route::get('/dashboard', [FinanceDashboardController::class, 'index']);

    Route::get('/payables', [FinancePayablesController::class, 'index']);
    Route::get('/cashflow/account', [FinanceCashflowController::class, 'accountSummary']);
    Route::get('/cashflow/transactions', [FinanceCashflowController::class, 'transactions']);
    Route::post('/cashflow/top-up', [FinanceCashflowController::class, 'topUp']);
    Route::post('/cashflow/adjust', [FinanceCashflowController::class, 'adjust']);
    Route::get('/receivables', [FinanceReceivablesController::class, 'index']);
    Route::get('/receivables/{source}/{id}', [FinanceReceivablesController::class, 'show']);
    Route::get('/payroll', [FinancePayrollController::class, 'index']);
    Route::get('/invoices', [FinanceInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [FinanceInvoiceController::class, 'show']);
    Route::post('/invoices/{id}/match', [FinanceInvoiceController::class, 'match']);
    Route::post('/invoices/{id}/approve', [FinanceInvoiceController::class, 'approve']);
    Route::post('/invoices/{id}/mark-paid', [FinanceInvoiceController::class, 'markPaid']);

    // Purchase Orders (Finance view) reusing procurement controller
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::get('/approved', [PurchaseOrderPrintEmailController::class, 'getApprovedOrders']);
        Route::get('/summary', [PurchaseOrderController::class, 'summary']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);

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

        // Pending receipt for finance visibility
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO']);
    });

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

