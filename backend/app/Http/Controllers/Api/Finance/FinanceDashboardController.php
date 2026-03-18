<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceExpense;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Supplier\SupplierPayment;
use App\Models\Hr\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $payables = Schema::hasTable('purchase_orders')
            ? PurchaseOrder::where('store_id', $storeId)
                ->where('payment_status', 'pending')
                ->sum('total_amount')
            : 0;

        $invoicesDue = Schema::hasTable('invoices')
            ? Invoice::where('store_id', $storeId)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('net_amount')
            : 0;

        $paymentsCompleted = 0;
        if (Schema::hasTable('supplier_payments')) {
            $paymentsQuery = SupplierPayment::query()->where('status', 'completed');
            if (Schema::hasColumn('supplier_payments', 'store_id')) {
                $paymentsQuery->where('store_id', $storeId);
            } elseif (Schema::hasColumn('supplier_payments', 'purchase_order_id')) {
                $paymentsQuery->whereHas('purchaseOrder', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });
            }
            $paymentsCompleted = $paymentsQuery->sum('payment_amount');
        }

        $expensesPending = Schema::hasTable('finance_expenses')
            ? FinanceExpense::where('store_id', $storeId)
                ->where('status', 'pending_approval')
                ->sum('amount')
            : 0;

        $payrollPending = Schema::hasTable('payrolls')
            ? Payroll::byUserStore()
                ->whereIn('status', ['pending', 'submitted', 'approved'])
                ->sum('net_salary')
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'payables' => $payables,
                'invoices_due' => $invoicesDue,
                'payments_completed' => $paymentsCompleted,
                'expenses_pending' => $expensesPending,
                'payroll_pending' => $payrollPending,
            ],
        ]);
    }
}
