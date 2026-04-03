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
    Route::get('/dashboard', [FinanceDashboardController::class, 'index'])->middleware('can:finance.dashboard.view');

    Route::get('/payables', [FinancePayablesController::class, 'index'])->middleware('can:finance.payables.view');
    Route::get('/cashflow/account', [FinanceCashflowController::class, 'accountSummary'])->middleware('can:finance.cashflow.view');
    Route::get('/cashflow/transactions', [FinanceCashflowController::class, 'transactions'])->middleware('can:finance.cashflow.view');
    Route::post('/cashflow/top-up', [FinanceCashflowController::class, 'topUp'])->middleware('can:finance.cashflow.manage');
    Route::get('/receivables', [FinanceReceivablesController::class, 'index'])->middleware('can:finance.receivables.view');
    Route::get('/receivables/{source}/{id}', [FinanceReceivablesController::class, 'show'])->middleware('can:finance.receivables.view');
    Route::get('/payroll', [FinancePayrollController::class, 'index'])->middleware('can:finance.payroll.view');
    Route::get('/invoices', [FinanceInvoiceController::class, 'index'])->middleware('can:finance.invoices.view');
    Route::get('/invoices/{id}', [FinanceInvoiceController::class, 'show'])->middleware('can:finance.invoices.view');
    Route::post('/invoices/{id}/match', [FinanceInvoiceController::class, 'match'])->middleware('can:finance.invoices.manage');
    Route::post('/invoices/{id}/approve', [FinanceInvoiceController::class, 'approve'])->middleware('can:finance.invoices.approve');
    Route::post('/invoices/{id}/mark-paid', [FinanceInvoiceController::class, 'markPaid'])->middleware('can:finance.invoices.approve');

    // Purchase Orders (Finance view) reusing procurement controller
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->middleware('can:finance.purchase-orders.view');
        Route::get('/approved', [PurchaseOrderPrintEmailController::class, 'getApprovedOrders'])->middleware('can:finance.purchase-orders.view');
        Route::get('/summary', [PurchaseOrderController::class, 'summary'])->middleware('can:finance.purchase-orders.view');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->middleware('can:finance.purchase-orders.view');

        Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('can:finance.purchase-orders.manage');
        Route::put('/{id}', [PurchaseOrderController::class, 'update'])->middleware('can:finance.purchase-orders.manage');
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->middleware('can:finance.purchase-orders.manage');

        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:finance.purchase-orders.approve');
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject'])->middleware('can:finance.purchase-orders.approve');
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send'])->middleware('can:finance.purchase-orders.manage');
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:finance.purchase-orders.manage');

        Route::get('/{id}/print', [PurchaseOrderPrintEmailController::class, 'generatePdf'])->middleware('can:finance.purchase-orders.view');
        Route::post('/{id}/email', [PurchaseOrderPrintEmailController::class, 'emailPo'])->middleware('can:finance.purchase-orders.manage');
        Route::get('/{id}/label', [PurchaseOrderPrintEmailController::class, 'generateLabel'])->middleware('can:finance.purchase-orders.view');
        Route::post('/{id}/request-revision', [PurchaseOrderPrintEmailController::class, 'requestRevision'])->middleware('can:finance.purchase-orders.manage');

        // Pending receipt for finance visibility
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO'])->middleware('can:finance.purchase-orders.view');
    });

    Route::get('/expenses', [FinanceExpenseController::class, 'index'])->middleware('can:finance.expenses.view');
    Route::post('/expenses', [FinanceExpenseController::class, 'store'])->middleware('can:finance.expenses.manage');
    Route::get('/expenses/{id}', [FinanceExpenseController::class, 'show'])->middleware('can:finance.expenses.view');
    Route::put('/expenses/{id}', [FinanceExpenseController::class, 'update'])->middleware('can:finance.expenses.manage');
    Route::post('/expenses/{id}/approve', [FinanceExpenseController::class, 'approve'])->middleware('can:finance.expenses.approve');
    Route::post('/expenses/{id}/reject', [FinanceExpenseController::class, 'reject'])->middleware('can:finance.expenses.approve');
    Route::post('/expenses/{id}/mark-paid', [FinanceExpenseController::class, 'markPaid'])->middleware('can:finance.expenses.approve');
    Route::delete('/expenses/{id}', [FinanceExpenseController::class, 'destroy'])->middleware('can:finance.expenses.manage');

    Route::get('/budgets', [FinanceBudgetController::class, 'index'])->middleware('can:finance.budgets.view');
    Route::post('/budgets', [FinanceBudgetController::class, 'store'])->middleware('can:finance.budgets.manage');
    Route::get('/budgets/{id}', [FinanceBudgetController::class, 'show'])->middleware('can:finance.budgets.view');
    Route::put('/budgets/{id}', [FinanceBudgetController::class, 'update'])->middleware('can:finance.budgets.manage');
    Route::delete('/budgets/{id}', [FinanceBudgetController::class, 'destroy'])->middleware('can:finance.budgets.manage');
});
