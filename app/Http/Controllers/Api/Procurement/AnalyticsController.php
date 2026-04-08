<?php
// backend/app/Http/Controllers/Api/Procurement/AnalyticsController.php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Analytics\SupplierPerformance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get procurement dashboard stats
     * GET /api/procurement/analytics/dashboard
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $dateFrom = $request->get('date_from', now()->subMonth()->toDateString());
            $dateTo = $request->get('date_to', now()->toDateString());

            $stats = [
                'total_suppliers' => Supplier::where('store_id', $storeId)->active()->count(),
                'total_po_amount' => PurchaseOrder::where('store_id', $storeId)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total_amount'),
                'pending_finance_approval_count' => PurchaseOrder::where('store_id', $storeId)
                    ->whereIn('status', ['draft', 'pending_finance_approval'])
                    ->count(),
                'pending_payment_count' => PurchaseOrder::where('store_id', $storeId)
                    ->where('payment_status', 'pending')
                    ->count(),
                'average_supplier_rating' => Supplier::where('store_id', $storeId)
                    ->active()
                    ->avg('rating'),
                'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($storeId),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reorder suggestions
     * GET /api/procurement/analytics/reorder-suggestions
     */
    public function getReorderSuggestions(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = $request->get('branch_id', auth()->user()->branch_id);

            $suggestions = DB::table('branch_inventory')
                ->join('products', 'branch_inventory.product_id', '=', 'products.id')
                ->where('branch_inventory.branch_id', $branchId)
                ->whereRaw('branch_inventory.quantity_on_hand < branch_inventory.reorder_point')
                ->when($request->has('product_type'), function ($q) use ($request) {
                    $q->where('products.product_type', $request->product_type);
                })
                ->select(
                    'products.id',
                    'products.sku',
                    'products.product_name',
                    'products.product_type',
                    'branch_inventory.quantity_on_hand',
                    'branch_inventory.reorder_point',
                    'branch_inventory.quantity_on_orders',
                    DB::raw('(branch_inventory.reorder_point - branch_inventory.quantity_on_hand + branch_inventory.quantity_on_orders) as suggested_qty')
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reorder suggestions',
            ], 500);
        }
    }

    /**
     * Get spend analytics
     * GET /api/procurement/analytics/spend
     */
    public function getSpendAnalytics(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $year = (int) $request->get('year', now()->year);
            $dateFrom = now()->setDate($year, 1, 1)->startOfDay();
            $dateTo = now()->setDate($year, 12, 31)->endOfDay();

            // Monthly spend + PO count
            $monthly = DB::table('purchase_orders')
                ->where('store_id', $storeId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
                ->selectRaw('SUM(total_amount) as amount')
                ->selectRaw('COUNT(id) as po_count')
                ->orderBy('month')
                ->get();

            // Top suppliers by spend
            $suppliers = DB::table('purchase_orders')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->where('purchase_orders.store_id', $storeId)
                ->whereBetween('purchase_orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('suppliers.id', 'suppliers.supplier_name')
                ->select(
                    'suppliers.id',
                    'suppliers.supplier_name',
                    DB::raw('SUM(purchase_orders.total_amount) as amount'),
                    DB::raw('COUNT(purchase_orders.id) as po_count')
                )
                ->orderByDesc('amount')
                ->limit(10)
                ->get();

            // Spend by category (using PO items)
            $categories = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                ->leftJoin('products', 'products.id', '=', 'purchase_order_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->where('purchase_orders.store_id', $storeId)
                ->whereBetween('purchase_orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('categories.id', 'categories.category_name')
                ->select(
                    DB::raw('COALESCE(categories.category_name, "Uncategorized") as category_name'),
                    DB::raw('SUM(purchase_order_items.line_total) as spend')
                )
                ->orderByDesc('spend')
                ->get();

            $totalSpend = (float) $monthly->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly' => $monthly,
                    'suppliers' => $suppliers,
                    'categories' => $categories,
                    'total_spend' => $totalSpend,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve spend analytics',
            ], 500);
        }
    }

    /**
     * Get supplier performance comparison
     * GET /api/procurement/analytics/supplier-performance
     */
    public function getSupplierPerformance(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $performance = Supplier::where('store_id', $storeId)
                ->active()
                ->select(
                    'id',
                    'supplier_name',
                    'rating',
                    'on_time_deliveries',
                    'late_deliveries',
                    'total_orders',
                    'total_amount_purchased'
                )
                ->withCount('purchaseOrders')
                ->orderByDesc('rating')
                ->limit(10)
                ->get()
                ->map(function($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->supplier_name,
                        'rating' => $supplier->rating,
                        'on_time_rate' => $supplier->on_time_delivery_rate,
                        'total_orders' => $supplier->total_orders,
                        'total_spent' => $supplier->total_amount_purchased,
                        'avg_order_value' => $supplier->average_order_value,
                        'status' => $supplier->rating >= 4.5 ? 'Top Performer' : 
                                  ($supplier->rating >= 3.0 ? 'Good' : 'Needs Review'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $performance,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier performance',
            ], 500);
        }
    }

    /**
     * Get receiving accuracy report
     * GET /api/procurement/analytics/receiving-accuracy
     */
    public function getReceivingAccuracy(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $dateFrom = $request->get('date_from', now()->subMonth()->toDateString());
            $dateTo = $request->get('date_to', now()->toDateString());

            $totalReceipts = GoodsReceipt::whereHas('purchaseOrder', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count();

            // Receipts with no discrepancies - this would need a discrepancy tracking mechanism
            // For now, we'll estimate based on quality checks
            $perfectReceipts = GoodsReceipt::whereHas('purchaseOrder', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
                ->where('receipt_status', 'full')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count();

            $accuracy = $totalReceipts > 0 ? ($perfectReceipts / $totalReceipts) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_receipts' => $totalReceipts,
                    'perfect_receipts' => $perfectReceipts,
                    'accuracy_rate' => round($accuracy, 2),
                    'accuracy_status' => $accuracy > 95 ? 'Excellent' : 
                                        ($accuracy > 90 ? 'Good' : 'Needs Improvement'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve receiving accuracy',
            ], 500);
        }
    }

    /**
     * Get budget tracking
     * GET /api/procurement/analytics/budget
     */
    public function getBudgetTracking(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;
            $branchId = $request->get('branch_id', auth()->user()->branch_id);
            $year = (int) $request->get('year', now()->year);
            $dateFrom = now()->setDate($year, 1, 1)->startOfDay();
            $dateTo = now()->setDate($year, 12, 31)->endOfDay();

            // Placeholder budgets; replace with real budget tables if available
            $annualBudget = 0;
            $monthlyBudget = 0;

            $ytdSpend = PurchaseOrder::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->sum('total_amount');

            $currentMonthSpend = PurchaseOrder::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('total_amount');

            // Monthly comparison (budget vs actual)
            $monthly = DB::table('purchase_orders')
                ->where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
                ->selectRaw('SUM(total_amount) as actual')
                ->orderBy('month')
                ->get()
                ->map(function ($row) use ($monthlyBudget) {
                    return [
                        'month' => $row->month,
                        'budgeted' => $monthlyBudget,
                        'actual' => (float) $row->actual,
                        'variance' => (float) $row->actual - $monthlyBudget,
                    ];
                });

            // Category-level budgets (placeholder)
            $categories = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                ->leftJoin('products', 'products.id', '=', 'purchase_order_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->where('purchase_orders.store_id', $storeId)
                ->where('purchase_orders.branch_id', $branchId)
                ->whereBetween('purchase_orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('categories.id', 'categories.category_name')
                ->select(
                    DB::raw('COALESCE(categories.id, 0) as category_id'),
                    DB::raw('COALESCE(categories.category_name, "Uncategorized") as category_name'),
                    DB::raw('SUM(purchase_order_items.line_total) as spent')
                )
                ->orderByDesc('spent')
                ->get()
                ->map(function ($row) {
                    return [
                        'category_id' => (int) $row->category_id,
                        'category_name' => $row->category_name,
                        'budget' => 0, // placeholder until real budgets exist
                        'spent' => (float) $row->spent,
                    ];
                });

            $data = [
                'annual_budget' => $annualBudget,
                'monthly_budget' => $monthlyBudget,
                'ytd_spend' => $ytdSpend,
                'current_month_spend' => $currentMonthSpend,
                'budget_status' => 'On Track', // placeholder
                'categories' => $categories,
                'monthly' => $monthly,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve budget tracking',
            ], 500);
        }
    }

    /**
     * Get lead time analysis
     * GET /api/procurement/analytics/lead-time
     */
    public function getLeadTimeAnalysis(Request $request): JsonResponse
    {
        try {
            $storeId = auth()->user()->store_id;

            $leadTimes = DB::table('purchase_orders')
                ->join('goods_receipts', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->where('purchase_orders.store_id', $storeId)
                ->select(
                    'suppliers.id',
                    'suppliers.supplier_name',
                    DB::raw('AVG(DATEDIFF(goods_receipts.created_at, purchase_orders.order_date)) as avg_lead_time')
                )
                ->groupBy('suppliers.id', 'suppliers.supplier_name')
                ->orderBy('avg_lead_time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $leadTimes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lead time analysis',
            ], 500);
        }
    }

    /**
     * Calculate on-time delivery rate
     */
    private function calculateOnTimeDeliveryRate(int $storeId): float
    {
        $totalDeliveries = Supplier::where('store_id', $storeId)
            ->active()
            ->sum(DB::raw('on_time_deliveries + late_deliveries'));

        if ($totalDeliveries === 0) {
            return 0;
        }

        $onTimeDeliveries = Supplier::where('store_id', $storeId)
            ->active()
            ->sum('on_time_deliveries');

        return round(($onTimeDeliveries / $totalDeliveries) * 100, 2);
    }
}
