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
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderPrintEmailController;
use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController;

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

    // Purchase Orders (Finance view) reusing procurement controller
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->middleware('can:finance.purchase_orders.view');
        Route::get('/approved', [PurchaseOrderPrintEmailController::class, 'getApprovedOrders'])->middleware('can:finance.purchase_orders.view');
        Route::get('/summary', [PurchaseOrderController::class, 'summary'])->middleware('can:finance.purchase_orders.view');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->middleware('can:finance.purchase_orders.view');

        Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('can:finance.purchase_orders.manage');
        Route::put('/{id}', [PurchaseOrderController::class, 'update'])->middleware('can:finance.purchase_orders.manage');
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy'])->middleware('can:finance.purchase_orders.manage');

        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve'])->middleware('can:finance.purchase_orders.approve');
        Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject'])->middleware('can:finance.purchase_orders.approve');
        Route::post('/{id}/send', [PurchaseOrderController::class, 'send'])->middleware('can:finance.purchase_orders.manage');
        Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:finance.purchase_orders.manage');

        Route::get('/{id}/print', [PurchaseOrderPrintEmailController::class, 'generatePdf'])->middleware('can:finance.purchase_orders.view');
        Route::post('/{id}/email', [PurchaseOrderPrintEmailController::class, 'emailPo'])->middleware('can:finance.purchase_orders.manage');
        Route::get('/{id}/label', [PurchaseOrderPrintEmailController::class, 'generateLabel'])->middleware('can:finance.purchase_orders.view');
        Route::post('/{id}/request-revision', [PurchaseOrderPrintEmailController::class, 'requestRevision'])->middleware('can:finance.purchase_orders.manage');

        // Pending receipt for finance visibility
        Route::get('/{poId}/pending-receipt', [GoodsReceiptController::class, 'pendingForPO'])->middleware('can:finance.purchase_orders.view');
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
