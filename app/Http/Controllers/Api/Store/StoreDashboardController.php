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
                'store' => (function () use ($storeId) {
                    $store = \App\Models\Store\Store::find($storeId);
                    return $store ? [
                        'verified' => $store->isVerified(),
                        'subscription_status' => $store->subscription_status,
                        'trial_days_remaining' => app(\App\Services\Store\StoreTrialService::class)->daysRemaining($store),
                    ] : null;
                })(),
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

            $posOrders = Schema::hasTable('sales_pos_orders')
                ? DB::table('sales_pos_orders')->where('store_id', $storeId)
                : null;
            $ecommerceOrders = Schema::hasTable('ecommerce_orders')
                ? DB::table('ecommerce_orders')->where('store_id', $storeId)
                : null;

            if ($posOrders) {
                $ordersQuery = $posOrders;

                $data['kpis']['sales_today'] = (float) $ordersQuery
                    ->clone()
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
                    ->sum('total_amount');

                $data['kpis']['orders_today'] = (int) $ordersQuery
                    ->clone()
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->count();

                $sales30d = (float) $ordersQuery
                    ->clone()
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
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
                    ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
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

            if ($ecommerceOrders) {
                $validEcommerceSales = fn ($query) => $query
                    ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                    ->whereNotIn('status', ['cancelled', 'rejected']);

                $ecommerceToday = (clone $ecommerceOrders)
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()]);
                $ecommerce30d = (clone $ecommerceOrders)->where('created_at', '>=', $last30Start->toDateTimeString());
                $data['kpis']['sales_today'] += (float) $validEcommerceSales(clone $ecommerceToday)->sum('total_amount');
                $data['kpis']['orders_today'] += (int) $ecommerceToday->count();
                $ecommerceSales30d = (float) $validEcommerceSales(clone $ecommerce30d)->sum('total_amount');
                $data['kpis']['sales_30d'] += $ecommerceSales30d;
                $totalOrders30d = (int) ($posOrders ? (clone $posOrders)->where('created_at', '>=', $last30Start->toDateTimeString())->whereIn('payment_status', ['paid', 'succeeded', 'completed'])->count() : 0) + (int) (clone $ecommerce30d)->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])->whereNotIn('status', ['cancelled', 'rejected'])->count();
                $data['kpis']['avg_order_value_30d'] = $totalOrders30d > 0
                    ? round($data['kpis']['sales_30d'] / $totalOrders30d, 2)
                    : 0;

                $ecommerceTrendRows = (clone $ecommerceOrders)
                    ->select('created_at', 'total_amount')
                    ->whereBetween('created_at', [$last7Start->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->whereIn(DB::raw('LOWER(COALESCE(payment_status, status, "pending"))'), ['paid', 'succeeded', 'completed'])
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->get();
                $ecommerceTrendMap = $ecommerceTrendRows->groupBy(fn ($row) => Carbon::parse($row->created_at)->setTimezone($tz)->toDateString())
                    ->map(fn ($group) => $group->sum('total_amount'));
                foreach ($data['charts']['sales_trend']['labels'] as $index => $label) {
                    $date = Carbon::now($tz)->subDays(6)->startOfDay()->addDays($index)->toDateString();
                    $data['charts']['sales_trend']['values'][$index] += (float) ($ecommerceTrendMap[$date] ?? 0);
                }

                $ecommerceStatuses = (clone $ecommerceOrders)
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->groupBy('status')->get();
                $statusTotals = array_combine(
                    $data['charts']['order_status']['labels'],
                    $data['charts']['order_status']['values']
                ) ?: [];
                foreach ($ecommerceStatuses as $row) {
                    $status = $row->status ?? 'unknown';
                    $statusTotals[$status] = ($statusTotals[$status] ?? 0) + (int) $row->total;
                }
                $data['charts']['order_status'] = [
                    'labels' => array_keys($statusTotals),
                    'values' => array_values($statusTotals),
                ];

                $ecommerceRecent = (clone $ecommerceOrders)->orderByDesc('created_at')->limit(6)
                    ->get(['order_number', 'shipping_name', 'status', 'total_amount', 'created_at'])
                    ->map(fn ($row) => [
                        'channel' => 'Ecommerce',
                        'order_number' => $row->order_number,
                        'customer_name' => $row->shipping_name,
                        'status' => $row->status,
                        'total_amount' => (float) $row->total_amount,
                        'created_at' => $row->created_at,
                    ]);
                $data['recent_orders'] = collect($data['recent_orders'])->map(fn ($row) => array_merge(['channel' => 'In-store'], $row))
                    ->concat($ecommerceRecent)->sortByDesc('created_at')->take(6)->values()->all();
            }

            if (Schema::hasTable('sales_pos_order_items')) {
                $itemsQuery = DB::table('sales_pos_order_items as items')
                    ->join('sales_pos_orders as orders', 'orders.id', '=', 'items.order_id')
                    ->where('orders.store_id', $storeId);

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

                if (Schema::hasTable('ecommerce_order_items') && $ecommerceOrders) {
                    $ecommerceTopProducts = DB::table('ecommerce_order_items as items')
                        ->join('ecommerce_orders as orders', 'orders.id', '=', 'items.order_id')
                        ->where('orders.store_id', $storeId)
                        ->where('orders.created_at', '>=', $last30Start->toDateTimeString())
                        ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                        ->select('items.product_name', DB::raw('SUM(items.quantity) as qty'))
                        ->groupBy('items.product_name')->get();
                    $productTotals = [];
                    foreach ($topProducts as $row) $productTotals[$row->product_name ?? 'Unknown'] = (int) $row->qty;
                    foreach ($ecommerceTopProducts as $row) $productTotals[$row->product_name ?? 'Unknown'] = ($productTotals[$row->product_name ?? 'Unknown'] ?? 0) + (int) $row->qty;
                    arsort($productTotals);
                    $productTotals = array_slice($productTotals, 0, 5, true);
                    $data['charts']['top_products'] = ['labels' => array_keys($productTotals), 'values' => array_values($productTotals)];
                }
            }

            if (Schema::hasTable('branch_inventory')) {
                $inventoryQuery = DB::table('branch_inventory')->where('store_id', $storeId);
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
                $data['kpis']['pending_requisitions'] = (int) $reqQuery
                    ->whereIn('status', ['draft', 'pending'])
                    ->count();
            }

            if (Schema::hasTable('purchase_orders')) {
                $poQuery = DB::table('purchase_orders')->where('store_id', $storeId);
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

                $branchSales = DB::table('sales_pos_orders')
                    ->where('store_id', $storeId)
                    ->where('created_at', '>=', $last30Start->toDateTimeString())
                    ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
                    ->select('branch_id', DB::raw('SUM(total_amount) as sales_30d'), DB::raw('COUNT(*) as orders_30d'))
                    ->groupBy('branch_id')->get()->keyBy('branch_id');
                $branchPosToday = DB::table('sales_pos_orders')
                    ->where('store_id', $storeId)
                    ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                    ->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
                    ->select('branch_id', DB::raw('SUM(total_amount) as sales_today'), DB::raw('COUNT(*) as orders_today'))
                    ->groupBy('branch_id')->get()->keyBy('branch_id');
                $branchEcommerceSales = Schema::hasTable('ecommerce_orders')
                    ? DB::table('ecommerce_orders')->where('store_id', $storeId)
                        ->where('created_at', '>=', $last30Start->toDateTimeString())
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->select('assigned_branch_id as branch_id', DB::raw('SUM(CASE WHEN LOWER(COALESCE(payment_status, status, "pending")) IN ("paid", "succeeded", "completed") THEN total_amount ELSE 0 END) as sales_30d'), DB::raw('COUNT(*) as orders_30d'))
                        ->groupBy('assigned_branch_id')->get()->keyBy('branch_id')
                    : collect();
                $branchEcommerceToday = Schema::hasTable('ecommerce_orders')
                    ? DB::table('ecommerce_orders')->where('store_id', $storeId)
                        ->whereBetween('created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()])
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->select('assigned_branch_id as branch_id', DB::raw('SUM(CASE WHEN LOWER(COALESCE(payment_status, status, "pending")) IN ("paid", "succeeded", "completed") THEN total_amount ELSE 0 END) as sales_today'), DB::raw('COUNT(*) as orders_today'))
                        ->groupBy('assigned_branch_id')->get()->keyBy('branch_id')
                    : collect();
                $branchInventory = Schema::hasTable('branch_inventory')
                    ? DB::table('branch_inventory')->where('store_id', $storeId)
                        ->select('branch_id', DB::raw("SUM(CASE WHEN stock_status = 'low_stock' THEN 1 ELSE 0 END) as low_stock_count"), DB::raw("SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_count"))
                        ->groupBy('branch_id')->get()->keyBy('branch_id')
                    : collect();

                $data['branches'] = array_map(function ($branch) use ($branchSales, $branchEcommerceSales, $branchPosToday, $branchEcommerceToday, $branchInventory) {
                    $pos = $branchSales->get($branch['id']);
                    $ecommerce = $branchEcommerceSales->get($branch['id']);
                    $posToday = $branchPosToday->get($branch['id']);
                    $ecommerceToday = $branchEcommerceToday->get($branch['id']);
                    $inventory = $branchInventory->get($branch['id']);
                    return array_merge($branch, [
                        'sales_today' => (float) ($posToday->sales_today ?? 0) + (float) ($ecommerceToday->sales_today ?? 0),
                        'orders_today' => (int) ($posToday->orders_today ?? 0) + (int) ($ecommerceToday->orders_today ?? 0),
                        'sales_30d' => (float) ($pos->sales_30d ?? 0) + (float) ($ecommerce->sales_30d ?? 0),
                        'orders_30d' => (int) ($pos->orders_30d ?? 0) + (int) ($ecommerce->orders_30d ?? 0),
                        'low_stock_count' => (int) ($inventory->low_stock_count ?? 0),
                        'out_of_stock_count' => (int) ($inventory->out_of_stock_count ?? 0),
                    ]);
                }, $data['branches']);
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
