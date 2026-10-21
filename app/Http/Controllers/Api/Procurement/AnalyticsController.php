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
     * Compare replenishment pressure and recent consumption across branches.
     * This is intentionally a transparent MVP forecast: recent stock-out/issue
     * pressure plus the branch's reorder settings, rather than a black-box model.
     * GET /api/procurement/analytics/forecasting
     */
    public function getForecasting(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $storeId = (int) ($user?->store_id ?? 0);
            $days = min(max((int) $request->get('days', 90), 30), 365);
            $branchId = (int) $request->get('branch_id', 0);

            if ($storeId <= 0) {
                return response()->json(['success' => false, 'message' => 'Your account is not assigned to a store.'], 422);
            }

            if ($branchId <= 0 && (int) ($user?->branch_id ?? 0) > 0 && !$user->hasRole('super_admin')) {
                $branchId = (int) $user->branch_id;
            }

            $branchesQuery = DB::table('branches')
                ->where('store_id', $storeId)
                ->where('status', 'active');
            if ($branchId > 0) {
                $branchesQuery->where('id', $branchId);
            }
            $branches = $branchesQuery->orderBy('name')->get(['id', 'name']);

            if ($branches->isEmpty()) {
                return response()->json(['success' => true, 'data' => [
                    'is_multi_branch' => false,
                    'branch_count' => 0,
                    'days' => $days,
                    'branch_summary' => [],
                    'top_items' => [],
                    'monthly_demand' => [],
                ]]);
            }

            $branchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->all();
            $inventory = DB::table('branch_inventory')
                ->join('products', 'branch_inventory.product_id', '=', 'products.id')
                ->where('branch_inventory.store_id', $storeId)
                ->whereIn('branch_inventory.branch_id', $branchIds)
                ->whereNull('branch_inventory.deleted_at')
                ->select([
                    'branch_inventory.branch_id',
                    'branch_inventory.product_id',
                    'products.product_name',
                    'products.sku',
                    'branch_inventory.quantity_available',
                    'branch_inventory.quantity_incoming',
                    'branch_inventory.reorder_point',
                    'branch_inventory.reorder_quantity',
                    'branch_inventory.safety_stock',
                ])->get();

            $since = now()->subDays($days)->startOfDay();
            $demand = DB::table('inventory_transactions')
                ->where('store_id', $storeId)
                ->whereIn('branch_id', $branchIds)
                ->where('quantity_change', '<', 0)
                ->where('transaction_date', '>=', $since)
                ->select('branch_id', 'product_id')
                ->selectRaw('SUM(ABS(quantity_change)) as consumed_qty')
                ->groupBy('branch_id', 'product_id')
                ->get()
                ->keyBy(fn ($row) => $row->branch_id . ':' . $row->product_id);

            $rows = $inventory->map(function ($row) use ($demand, $days) {
                $demandRow = $demand->get($row->branch_id . ':' . $row->product_id);
                $consumed = (float) ($demandRow?->consumed_qty ?? 0);
                $dailyDemand = $consumed / $days;
                $available = (float) $row->quantity_available;
                $incoming = (float) $row->quantity_incoming;
                $reorderPoint = (float) $row->reorder_point;
                $reorderQuantity = (float) $row->reorder_quantity;
                $shortage = max($reorderPoint - $available, 0);
                $projected30DayDemand = $dailyDemand * 30;
                $recommended = max($reorderQuantity, $projected30DayDemand + (float) $row->safety_stock - $available - $incoming, 0);

                return [
                    'branch_id' => (int) $row->branch_id,
                    'product_id' => (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'sku' => $row->sku,
                    'available_qty' => $available,
                    'incoming_qty' => $incoming,
                    'reorder_point' => $reorderPoint,
                    'consumed_qty' => round($consumed, 2),
                    'daily_demand' => round($dailyDemand, 2),
                    'projected_30_day_demand' => round($projected30DayDemand, 2),
                    'shortage_qty' => round($shortage, 2),
                    'recommended_qty' => round($recommended, 2),
                    'needs_replenishment' => $available <= $reorderPoint,
                ];
            });

            $branchSummary = $branches->map(function ($branch) use ($rows) {
                $branchRows = $rows->where('branch_id', (int) $branch->id);
                return [
                    'branch_id' => (int) $branch->id,
                    'branch_name' => $branch->name,
                    'tracked_items' => $branchRows->count(),
                    'items_needing_replenishment' => $branchRows->where('needs_replenishment', true)->count(),
                    'out_of_stock_items' => $branchRows->where('available_qty', '<=', 0)->count(),
                    'shortage_qty' => round($branchRows->sum('shortage_qty'), 2),
                    'recommended_qty' => round($branchRows->sum('recommended_qty'), 2),
                    'consumed_qty' => round($branchRows->sum('consumed_qty'), 2),
                    'pressure_score' => round($branchRows->sum('shortage_qty') + ($branchRows->where('available_qty', '<=', 0)->count() * 5), 2),
                ];
            })->sortByDesc('pressure_score')->values();

            $topItems = $rows->filter(fn ($row) => $row['needs_replenishment'])
                ->sortByDesc(fn ($row) => $row['shortage_qty'] + $row['daily_demand'])
                ->take(10)->values();

            $monthlyDemand = DB::table('inventory_transactions')
                ->where('store_id', $storeId)
                ->whereIn('branch_id', $branchIds)
                ->where('quantity_change', '<', 0)
                ->where('transaction_date', '>=', $since)
                ->groupByRaw("DATE_FORMAT(transaction_date, '%Y-%m')")
                ->orderByRaw("DATE_FORMAT(transaction_date, '%Y-%m')")
                ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month")
                ->selectRaw('SUM(ABS(quantity_change)) as consumed_qty')
                ->get()
                ->map(fn ($row) => ['month' => $row->month, 'consumed_qty' => round((float) $row->consumed_qty, 2)])
                ->values();

            return response()->json(['success' => true, 'data' => [
                'is_multi_branch' => $branches->count() > 1,
                'branch_count' => $branches->count(),
                'days' => $days,
                'branch_summary' => $branchSummary,
                'top_items' => $topItems,
                'monthly_demand' => $monthlyDemand,
            ]]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve procurement forecasting data.'], 500);
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
