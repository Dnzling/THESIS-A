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
use App\Http\Controllers\Api\Finance\FinanceRefundController;
use App\Http\Controllers\Api\Finance\FinanceLiquidationController;
use App\Http\Controllers\Api\Finance\FinanceTaxVatController;
use App\Http\Controllers\Api\Store\SimplePayrollController;
use App\Http\Controllers\Api\Store\BranchExpenseController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\PurchaseOrder\PurchaseOrderPrintEmailController;
use App\Http\Controllers\Api\Procurement\Receiving\GoodsReceiptController;

Route::prefix('finance')->group(function () {
    Route::get('/dashboard/trend', [\App\Http\Controllers\Api\DashboardTrendController::class, 'finance']);
    Route::get('/dashboard', [FinanceDashboardController::class, 'index']);
    Route::get('/tax-vat', [FinanceTaxVatController::class, 'index'])->middleware('can:finance.tax-vat.view');

    Route::prefix('refunds')->group(function () {
        Route::get('/', [FinanceRefundController::class, 'index'])->middleware('can:finance.refunds.view');
        Route::post('/', [FinanceRefundController::class, 'store'])->middleware('can:finance.refunds.manage');
        Route::get('/{refund}', [FinanceRefundController::class, 'show'])->middleware('can:finance.refunds.view');
        Route::put('/{refund}/status', [FinanceRefundController::class, 'updateStatus'])->middleware('can:finance.refunds.approve');
        Route::post('/{refund}/send', [FinanceRefundController::class, 'markSent'])->middleware('can:finance.refunds.approve');
        Route::post('/{refund}/paymongo/check', [FinanceRefundController::class, 'refreshPaymongoRefund'])->middleware('can:finance.refunds.approve');
    });

    Route::prefix('liquidations')->group(function () {
        Route::get('/', [FinanceLiquidationController::class, 'index'])->middleware('can:finance.liquidations.view');
        Route::post('/', [FinanceLiquidationController::class, 'store'])->middleware('can:finance.liquidations.manage');
        Route::get('/{liquidation}', [FinanceLiquidationController::class, 'show'])->middleware('can:finance.liquidations.view');
        Route::post('/{liquidation}/approve', [FinanceLiquidationController::class, 'approve'])->middleware('can:finance.liquidations.approve');
        Route::post('/{liquidation}/reject', [FinanceLiquidationController::class, 'reject'])->middleware('can:finance.liquidations.approve');
        Route::post('/{liquidation}/release', [FinanceLiquidationController::class, 'release'])->middleware('can:finance.liquidations.approve');
        Route::post('/{liquidation}/submit', [FinanceLiquidationController::class, 'submit'])->middleware('can:finance.liquidations.manage');
        Route::post('/{liquidation}/settle', [FinanceLiquidationController::class, 'settle'])->middleware('can:finance.liquidations.approve');
    });

    Route::get('/payables', [FinancePayablesController::class, 'index']);
    Route::get('/cashflow/account', [FinanceCashflowController::class, 'accountSummary']);
    Route::get('/cashflow/transactions', [FinanceCashflowController::class, 'transactions']);
    Route::get('/cashflow/transactions/export', [FinanceCashflowController::class, 'exportTransactions']);
    Route::get('/cashflow/transactions/print', [FinanceCashflowController::class, 'printTransactions']);
    Route::get('/cashflow/transactions/{id}', [FinanceCashflowController::class, 'transactionDetail']);
    Route::get('/receivables', [FinanceReceivablesController::class, 'index']);
    Route::get('/receivables/{source}/{id}', [FinanceReceivablesController::class, 'show']);
    Route::get('/payroll', [FinancePayrollController::class, 'index'])->middleware('can:finance.payroll.view');
    Route::prefix('payroll')->group(function (): void {
        Route::get('/pay-periods/{id}/payroll', [FinancePayrollController::class, 'periodPayroll'])->middleware('can:finance.payroll.view');
        Route::post('/{id}/submit', [FinancePayrollController::class, 'submit'])->middleware('can:finance.payroll.manage');
        Route::post('/{id}/approve', [FinancePayrollController::class, 'approve'])->middleware('can:finance.payroll.approve');
        Route::post('/{id}/release', [FinancePayrollController::class, 'release'])->middleware('can:finance.payroll.approve');
        Route::post('/{id}/mark-paid', [FinancePayrollController::class, 'markPaid'])->middleware('can:finance.payroll.approve');
        Route::post('/bulk-submit', [FinancePayrollController::class, 'bulkSubmit'])->middleware('can:finance.payroll.manage');
        Route::post('/bulk-approve', [FinancePayrollController::class, 'bulkApprove'])->middleware('can:finance.payroll.approve');
        Route::post('/bulk-mark-paid', [FinancePayrollController::class, 'bulkMarkPaid'])->middleware('can:finance.payroll.approve');

        Route::prefix('basic')->group(function (): void {
            Route::get('/periods', [SimplePayrollController::class, 'periods'])->middleware('can:finance.payroll.view');
            Route::get('/periods/{periodId}', [SimplePayrollController::class, 'periodDetail'])->middleware('can:finance.payroll.view');
            Route::get('/preview', [SimplePayrollController::class, 'preview'])->middleware('can:finance.payroll.view');
            Route::post('/generate', [SimplePayrollController::class, 'generate'])->middleware('can:finance.payroll.manage');
            Route::put('/{payrollId}', [SimplePayrollController::class, 'updateDraft'])->middleware('can:finance.payroll.manage');
            Route::post('/{payrollId}/complete', [SimplePayrollController::class, 'complete'])->middleware('can:finance.payroll.approve');
            Route::post('/bulk-complete', [SimplePayrollController::class, 'bulkComplete'])->middleware('can:finance.payroll.approve');
            Route::post('/bulk-pay', [SimplePayrollController::class, 'bulkPay'])->middleware('can:finance.payroll.approve');
        });
    });

    Route::get('/invoices', [FinanceInvoiceController::class, 'index'])->middleware('can:finance.invoices.view');
    Route::post('/invoices', [FinanceInvoiceController::class, 'store'])->middleware('can:finance.invoices.manage');
    Route::get('/invoices/{id}', [FinanceInvoiceController::class, 'show'])->whereNumber('id')->middleware('can:finance.invoices.view');
    Route::put('/invoices/{id}', [FinanceInvoiceController::class, 'update'])->whereNumber('id')->middleware('can:finance.invoices.manage');
    Route::post('/invoices/{id}/match', [FinanceInvoiceController::class, 'match'])->whereNumber('id')->middleware('can:finance.invoices.manage');
    Route::post('/invoices/{id}/approve', [FinanceInvoiceController::class, 'approve'])->whereNumber('id')->middleware('can:finance.invoices.approve');
    Route::post('/invoices/{id}/mark-paid', [FinanceInvoiceController::class, 'markPaid'])->whereNumber('id')->middleware('can:finance.invoices.approve');

    Route::get('/expenses/benefit-requests/{id}', [FinanceExpenseController::class, 'benefitRequest'])->whereNumber('id')->middleware('can:finance.expenses.view');
    Route::get('/expenses/{financeExpenseId}/receipt', [BranchExpenseController::class, 'financeReceipt'])->whereNumber('financeExpenseId')->middleware('can:finance.expenses.view');

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
