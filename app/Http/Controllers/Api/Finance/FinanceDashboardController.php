<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceExpense;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Invoice\Invoice;
use App\Models\Procurement\Supplier\SupplierPayment;
use App\Models\Hr\Payroll;
use App\Models\ProductCatalog\Product;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\StockAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceDashboardController extends Controller
{
    private const HIGH_VALUE_SUPPLIER_PAYMENT_THRESHOLD = 100000;
    private const HIGH_IMPACT_INVENTORY_THRESHOLD = 50000;

    public function index(Request $request): JsonResponse
    {
        $storeId = $request->user()?->store_id;

        $currentEnd = now()->endOfDay();
        $currentStart = now()->startOfDay()->subDays(27);
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $currentStart->copy()->subDays(28);
        $sumPeriod = static fn ($query, string $dateColumn, string $amountColumn, $start, $end) =>
            (clone $query)->whereBetween($dateColumn, [$start, $end])->sum($amountColumn);

        $payables = Schema::hasTable('purchase_orders')
            ? PurchaseOrder::where('store_id', $storeId)
                ->where('payment_status', 'pending')
                ->whereBetween('order_date', [$currentStart, $currentEnd])
                ->sum('total_amount')
            : 0;
        $previousPayables = Schema::hasTable('purchase_orders')
            ? $sumPeriod(PurchaseOrder::where('store_id', $storeId)->where('payment_status', 'pending'), 'order_date', 'total_amount', $previousStart, $previousEnd)
            : 0;

        $invoicesDue = Schema::hasTable('invoices')
            ? Invoice::where('store_id', $storeId)
                ->whereIn('status', ['pending', 'approved'])
                ->whereBetween('invoice_date', [$currentStart, $currentEnd])
                ->sum('net_amount')
            : 0;
        $previousInvoicesDue = Schema::hasTable('invoices')
            ? $sumPeriod(Invoice::where('store_id', $storeId)->whereIn('status', ['pending', 'approved']), 'invoice_date', 'net_amount', $previousStart, $previousEnd)
            : 0;

        $paymentsCompleted = 0;
        $previousPaymentsCompleted = 0;
        if (Schema::hasTable('supplier_payments')) {
            $paymentsQuery = SupplierPayment::query()->where('status', 'completed');
            if (Schema::hasColumn('supplier_payments', 'store_id')) {
                $paymentsQuery->where('store_id', $storeId);
            } elseif (Schema::hasColumn('supplier_payments', 'purchase_order_id')) {
                $paymentsQuery->whereHas('purchaseOrder', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                });
            } else {
                $paymentsQuery = null;
            }
            if ($paymentsQuery && Schema::hasColumn('supplier_payments', 'payment_date')) {
                $paymentsCompleted = $sumPeriod(clone $paymentsQuery, 'payment_date', 'payment_amount', $currentStart, $currentEnd);
                $previousPaymentsCompleted = $sumPeriod(clone $paymentsQuery, 'payment_date', 'payment_amount', $previousStart, $previousEnd);
            }
        }

        $expensesPending = Schema::hasTable('finance_expenses')
            ? FinanceExpense::where('store_id', $storeId)
                ->where('status', 'pending_approval')
                ->whereBetween('expense_date', [$currentStart, $currentEnd])
                ->sum('amount')
            : 0;
        $previousExpensesPending = Schema::hasTable('finance_expenses')
            ? $sumPeriod(FinanceExpense::where('store_id', $storeId)->where('status', 'pending_approval'), 'expense_date', 'amount', $previousStart, $previousEnd)
            : 0;

        $payrollPending = Schema::hasTable('payrolls')
            ? Payroll::byUserStore()
                ->whereIn('status', ['pending', 'submitted', 'processing'])
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->sum('net_salary')
            : 0;
        $previousPayrollPending = Schema::hasTable('payrolls')
            ? $sumPeriod(Payroll::byUserStore()->whereIn('status', ['pending', 'submitted', 'processing']), 'created_at', 'net_salary', $previousStart, $previousEnd)
            : 0;

        $hrPayrollForFinanceApprovalCount = Schema::hasTable('payrolls')
            ? Payroll::byUserStore()
                ->whereIn('status', ['pending', 'submitted', 'processing'])
                ->count()
            : 0;

        $procurementPoForFinanceApprovalCount = Schema::hasTable('purchase_orders')
            ? PurchaseOrder::query()
                ->where('store_id', $storeId)
                ->where('status', 'pending_finance_approval')
                ->count()
            : 0;

        $highValueSupplierPaymentsForFinanceApprovalCount = Schema::hasTable('supplier_payments')
            ? SupplierPayment::query()
                ->when(
                    Schema::hasColumn('supplier_payments', 'store_id'),
                    fn ($q) => $q->where('store_id', $storeId),
                    fn ($q) => Schema::hasColumn('supplier_payments', 'purchase_order_id')
                        ? $q->whereHas('purchaseOrder', fn ($po) => $po->where('store_id', $storeId))
                        : $q->whereRaw('1 = 0')
                )
                ->where('status', 'pending_approval')
                ->where('payment_amount', '>=', self::HIGH_VALUE_SUPPLIER_PAYMENT_THRESHOLD)
                ->count()
            : 0;

        $inventoryHighImpactForFinanceApprovalCount = 0;
        if (Schema::hasTable('inventory_transactions')) {
            $inventoryHighImpactForFinanceApprovalCount = InventoryTransaction::query()
                ->where('store_id', $storeId)
                ->where('requires_approval', true)
                ->where('approval_status', 'pending')
                ->whereRaw('ABS(COALESCE(total_value, 0)) >= ?', [self::HIGH_IMPACT_INVENTORY_THRESHOLD])
                ->count();
        } elseif (Schema::hasTable('stock_adjustments')) {
            $inventoryHighImpactForFinanceApprovalCount = StockAdjustment::query()
                ->where('store_id', $storeId)
                ->where('status', 'pending_approval')
                ->count();
        }

        $approvalsNeeded = [
            [
                'source_module' => 'Procurement',
                'workflow' => 'PO approvals',
                'target_approval' => 'Finance Approval',
                'pending_count' => $procurementPoForFinanceApprovalCount,
                'route' => '/system/procurement/purchase-orders',
            ],
            [
                'source_module' => 'HR',
                'workflow' => 'Generate Payroll',
                'target_approval' => 'Finance Approval',
                'pending_count' => $hrPayrollForFinanceApprovalCount,
                'route' => '/system/finance/payroll',
            ],
            [
                'source_module' => 'Procurement',
                'workflow' => 'High-value supplier payments',
                'target_approval' => 'Finance Approval',
                'pending_count' => $highValueSupplierPaymentsForFinanceApprovalCount,
                'route' => '/system/finance/payables',
            ],
            [
                'source_module' => 'Inventory',
                'workflow' => 'High-impact adjustments',
                'target_approval' => 'Finance Approval',
                'pending_count' => $inventoryHighImpactForFinanceApprovalCount,
                'route' => '/system/inventory/adjustments',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'payables' => $payables,
                'invoices_due' => $invoicesDue,
                'payments_completed' => $paymentsCompleted,
                'expenses_pending' => $expensesPending,
                'payroll_pending' => $payrollPending,
                'period' => [
                    'start' => $currentStart->toDateString(),
                    'end' => $currentEnd->toDateString(),
                    'previous_start' => $previousStart->toDateString(),
                    'previous_end' => $previousEnd->toDateString(),
                ],
                'comparisons' => [
                    'payables' => $previousPayables,
                    'invoices_due' => $previousInvoicesDue,
                    'payments_completed' => $previousPaymentsCompleted,
                    'expenses_pending' => $previousExpensesPending,
                    'payroll_pending' => $previousPayrollPending,
                ],
                'approvals_needed' => $approvalsNeeded,
            ],
        ]);
    }
}
