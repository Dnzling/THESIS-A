<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $storeId = $user->store_id ?? ($user->employee?->store_id ?? null);
            $branchId = $user->branch_id ?? ($user->employee?->branch_id ?? null);

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found for user.',
                ], 400);
            }

            // Use application timezone-aware day boundaries, convert to UTC for DB comparisons
            $tz = config('app.timezone') ?? 'UTC';
            $nowTz = Carbon::now($tz);
            $todayStart = $nowTz->copy()->startOfDay()->setTimezone('UTC');
            $todayEnd = $nowTz->copy()->endOfDay()->setTimezone('UTC');

            $last7Start = $nowTz->copy()->subDays(6)->startOfDay()->setTimezone('UTC');
            $last30Start = $nowTz->copy()->subDays(29)->startOfDay()->setTimezone('UTC');

            $data = [
                'kpis' => [
                    'sales_today' => 0,
                    'orders_today' => 0,
                    'sales_30d' => 0,
                    'avg_order_value_30d' => 0,
                    'low_stock_count' => 0,
                    'out_of_stock_count' => 0,
                    'pending_requisitions' => 0,
                    'pending_purchase_orders' => 0,
                ],
                'charts' => [
                    'sales_trend' => ['labels' => [], 'values' => []],
                    'order_status' => ['labels' => [], 'values' => []],
                    'top_products' => ['labels' => [], 'values' => []],
                ],
                'branches' => [],
                'recent_orders' => [],
            ];

            if (Schema::hasTable('sales_pos_orders')) {
                $ordersQuery = DB::table('sales_pos_orders')
                    ->where('store_id', $storeId);

                if ($branchId) {
                    $ordersQuery->where('branch_id', $branchId);
                }

                $data['kpis']['sales_today'] = (float) $ordersQuery
                    ->clone()
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->sum('total_amount');

                $data['kpis']['orders_today'] = (int) $ordersQuery
                    ->clone()
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->count();

                $sales30d = (float) $ordersQuery
                    ->clone()
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->sum('total_amount');

                $orders30d = (int) $ordersQuery
                    ->clone()
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->count();

                $data['kpis']['sales_30d'] = $sales30d;
                $data['kpis']['avg_order_value_30d'] = $orders30d > 0
                    ? round($sales30d / $orders30d, 2)
                    : 0;

                // Trend: aggregate totals per day for last 7 days using timezone-aware grouping in PHP
                $trendRows = $ordersQuery
                    ->clone()
                    ->select('created_at', 'total_amount')
                    ->whereBetween('created_at', [$last7Start->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->orderBy('created_at')
                    ->get();

                $trendMap = $trendRows->groupBy(function ($row) use ($tz) {
                    return Carbon::parse($row->created_at)->setTimezone($tz)->toDateString();
                })->map(function ($group) {
                    return $group->sum('total_amount');
                });

                $labels = [];
                $values = [];
                for ($i = 0; $i < 7; $i++) {
                    $date = Carbon::now($tz)->subDays(6)->startOfDay()->addDays($i);
                    $key = $date->toDateString();
                    $labels[] = $date->format('M d');
                    $values[] = (float) ($trendMap[$key] ?? 0);
                }
                $data['charts']['sales_trend'] = ['labels' => $labels, 'values' => $values];

                $statusRows = $ordersQuery
                    ->clone()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->groupBy('status')
                    ->orderBy('total', 'desc')
                    ->get();
                $data['charts']['order_status'] = [
                    'labels' => $statusRows->pluck('status')->map(fn ($s) => $s ?? 'unknown')->toArray(),
                    'values' => $statusRows->pluck('total')->map(fn ($t) => (int) $t)->toArray(),
                ];

                $data['recent_orders'] = $ordersQuery
                    ->clone()
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get(['order_number', 'customer_name', 'status', 'total_amount', 'created_at'])
                    ->map(function ($row) {
                        return [
                            'order_number' => $row->order_number,
                            'customer_name' => $row->customer_name,
                            'status' => $row->status,
                            'total_amount' => (float) $row->total_amount,
                            'created_at' => $row->created_at,
                        ];
                    })
                    ->toArray();
            }

            if (Schema::hasTable('sales_pos_order_items')) {
                $itemsQuery = DB::table('sales_pos_order_items as items')
                    ->join('sales_pos_orders as orders', 'orders.id', '=', 'items.order_id')
                    ->where('orders.store_id', $storeId);

                if ($branchId) {
                    $itemsQuery->where('orders.branch_id', $branchId);
                }

                $topProducts = $itemsQuery
                    ->select('items.product_name', DB::raw('SUM(items.quantity) as qty'))
                    ->where('orders.created_at', '>=', $last30Start->toDateTimeString())
                    ->groupBy('items.product_name')
                    ->orderByDesc('qty')
                    ->limit(5)
                    ->get();

                $data['charts']['top_products'] = [
                    'labels' => $topProducts->pluck('product_name')->map(fn ($p) => $p ?? 'Unknown')->toArray(),
                    'values' => $topProducts->pluck('qty')->map(fn ($q) => (int) $q)->toArray(),
                ];
            }

            if (Schema::hasTable('branch_inventory')) {
                $inventoryQuery = DB::table('branch_inventory')->where('store_id', $storeId);
                if ($branchId) {
                    $inventoryQuery->where('branch_id', $branchId);
                }

                $data['kpis']['low_stock_count'] = (int) $inventoryQuery
                    ->clone()
                    ->where('stock_status', 'low_stock')
                    ->count();

                $data['kpis']['out_of_stock_count'] = (int) $inventoryQuery
                    ->clone()
                    ->where('stock_status', 'out_of_stock')
                    ->count();
            }

            if (Schema::hasTable('purchase_requisitions')) {
                $reqQuery = DB::table('purchase_requisitions')->where('store_id', $storeId);
                if ($branchId) {
                    $reqQuery->where('branch_id', $branchId);
                }
                $data['kpis']['pending_requisitions'] = (int) $reqQuery
                    ->whereIn('status', ['draft', 'pending'])
                    ->count();
            }

            if (Schema::hasTable('purchase_orders')) {
                $poQuery = DB::table('purchase_orders')->where('store_id', $storeId);
                if ($branchId) {
                    $poQuery->where('branch_id', $branchId);
                }
                $data['kpis']['pending_purchase_orders'] = (int) $poQuery
                    ->whereIn('status', ['pending', 'for_approval', 'awaiting_approval'])
                    ->count();
            }

            if (Schema::hasTable('branches')) {
                $branchesQuery = DB::table('branches')
                    ->where('store_id', $storeId)
                    ->orderByDesc('is_main_branch')
                    ->orderBy('name');
                $data['branches'] = $branchesQuery->get([
                    'id',
                    'name',
                    'city',
                    'province',
                    'status',
                    'is_main_branch',
                    'branch_type',
                ])->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'name' => $row->name,
                        'city' => $row->city,
                        'province' => $row->province,
                        'status' => $row->status,
                        'is_main_branch' => (bool) $row->is_main_branch,
                        'branch_type' => $row->branch_type,
                    ];
                })->toArray();
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
