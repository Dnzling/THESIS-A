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
            } else {
                $paymentsQuery = null;
            }
            $paymentsCompleted = $paymentsQuery ? $paymentsQuery->sum('payment_amount') : 0;
        }

        $expensesPending = Schema::hasTable('finance_expenses')
            ? FinanceExpense::where('store_id', $storeId)
                ->where('status', 'pending_approval')
                ->sum('amount')
            : 0;

        $payrollPending = Schema::hasTable('payrolls')
            ? Payroll::byUserStore()
                ->whereIn('status', ['pending', 'submitted', 'processing'])
                ->sum('net_salary')
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

        $merchPriceForFinanceApprovalCount = Schema::hasTable('products')
            ? Product::query()
                ->where('store_id', $storeId)
                ->where('price_approval_status', 'pending')
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
                'source_module' => 'Merchandising',
                'workflow' => 'Price changes',
                'target_approval' => 'Finance Approval',
                'pending_count' => $merchPriceForFinanceApprovalCount,
                'route' => '/system/merchandising/products',
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
                'approvals_needed' => $approvalsNeeded,
            ],
        ]);
    }
}
