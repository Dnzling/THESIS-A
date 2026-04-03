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
            $dateFrom = $request->get('date_from', now()->subYear()->toDateString());
            $dateTo = $request->get('date_to', now()->toDateString());

            $bySupplier = DB::table('purchase_orders')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->where('purchase_orders.store_id', $storeId)
                ->whereBetween('purchase_orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('suppliers.id', 'suppliers.supplier_name')
                ->select(
                    'suppliers.id',
                    'suppliers.supplier_name',
                    DB::raw('SUM(purchase_orders.total_amount) as total_spend'),
                    DB::raw('COUNT(purchase_orders.id) as order_count')
                )
                ->orderByDesc('total_spend')
                ->limit(10)
                ->get();

            $byMonth = DB::table('purchase_orders')
                ->where('store_id', $storeId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupByRaw('MONTH(created_at)')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(total_amount) as total_spend')
                )
                ->orderBy('month', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'by_supplier' => $bySupplier,
                    'by_month' => $byMonth,
                    'total_spend' => $bySupplier->sum('total_spend'),
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

            // This would need a budget table in your system
            // For now, returning a template structure
            $budgetTracking = [
                'annual_budget' => 0, // Get from settings or branch settings
                'ytd_spend' => PurchaseOrder::where('store_id', $storeId)
                    ->where('branch_id', $branchId)
                    ->whereBetween('created_at', [now()->startOfYear(), now()])
                    ->sum('total_amount'),
                'monthly_budget' => 0,
                'current_month_spend' => PurchaseOrder::where('store_id', $storeId)
                    ->where('branch_id', $branchId)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])
                    ->sum('total_amount'),
                'budget_status' => 'On Track', // Calculate based on thresholds
            ];

            return response()->json([
                'success' => true,
                'data' => $budgetTracking,
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
