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

class FinanceDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $payables = PurchaseOrder::where('store_id', $storeId)
            ->where('payment_status', 'pending')
            ->sum('total_amount');

        $invoicesDue = Invoice::where('store_id', $storeId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('net_amount');

        $paymentsCompleted = SupplierPayment::whereHas('purchaseOrder', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->where('status', 'completed')
            ->sum('payment_amount');

        $expensesPending = FinanceExpense::where('store_id', $storeId)
            ->where('status', 'pending_approval')
            ->sum('amount');

        $payrollPending = Payroll::byUserStore()
            ->whereIn('status', ['pending', 'submitted', 'approved'])
            ->sum('net_salary');

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
