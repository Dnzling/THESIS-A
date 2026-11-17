<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\ForecastPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForecastController extends Controller
{
    public function index(Request $request, string $scope): JsonResponse
    {
        abort_unless(in_array($scope, ['inventory', 'warehouse'], true), 404);
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id);
        $assignedBranch = (int) ($user?->branch_id ?: $user?->employee?->branch_id);
        abort_if($storeId < 1, 403);
        $plan = app(ForecastPlanService::class);
        abort_unless($plan->allowsForecast($storeId), 403, 'Forecasting requires Growth or Enterprise. Use manual reorder points on this plan.');

        if ($scope === 'warehouse' && $request->filled('inventory_id')) {
            return $this->productReport($request, $storeId, $assignedBranch);
        }

        $validated = $request->validate([
            'from_date' => ['nullable', 'required_with:to_date', 'date', 'before_or_equal:today'],
            'to_date' => ['nullable', 'required_with:from_date', 'date', 'after_or_equal:from_date', 'before_or_equal:today'],
        ]);
        $end = isset($validated['to_date'])
            ? \Carbon\Carbon::parse($validated['to_date'])->endOfDay()
            : now()->endOfDay();
        $start = isset($validated['from_date'])
            ? \Carbon\Carbon::parse($validated['from_date'])->startOfDay()
            : $end->copy()->subDays(29)->startOfDay();
        abort_if($start->diffInDays($end->copy()->startOfDay()) + 1 > 366, 422, 'Choose a date range of up to one year.');
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $branchId = (int) $request->integer('branch_id');
        if ($assignedBranch > 0) {
            abort_if($branchId > 0 && $branchId !== $assignedBranch, 403);
            $branchId = $assignedBranch;
        }
        $branches = DB::table('branches')->where('store_id', $storeId);
        // The inventory view covers retail branches; the warehouse view covers only warehouse branches.
        $branches = $scope === 'warehouse'
            ? $branches->where('branch_type', 'warehouse')
            : $branches->where('branch_type', '!=', 'warehouse');
        $branches = $branches->when($branchId, fn ($q) => $q->where('id', $branchId))->get(['id', 'name']);
        $branchIds = $branches->pluck('id')->all();
        $stock = DB::table('branch_inventory as bi')
            ->join('products as p', 'p.id', '=', 'bi.product_id')
            ->leftJoin('product_variations as v', 'v.id', '=', 'bi.variation_id')
            ->where('bi.store_id', $storeId)->whereIn('bi.branch_id', $branchIds)
            ->whereNull('bi.deleted_at')->whereNull('p.deleted_at')
            ->where('p.product_type', 'finished_good')
            ->select('bi.id', 'bi.branch_id', 'bi.product_id', 'bi.variation_id', 'bi.quantity_available', 'bi.quantity_incoming', 'bi.reorder_point', 'bi.safety_stock', 'p.product_name')
            ->selectRaw('COALESCE(v.variation_sku, p.sku) as sku')
            ->get();

        $sales = DB::table('inventory_transactions as t')
            ->join('products as p', 'p.id', '=', 't.product_id')
            ->where('p.product_type', 'finished_good')
            ->where('t.store_id', $storeId)->whereIn('t.branch_id', $branchIds)
            ->where('t.quantity_change', '<', 0)->whereBetween('t.transaction_date', [$start, $end])
            ->selectRaw('t.branch_id, t.product_id, t.variation_id, SUM(ABS(t.quantity_change)) as units')
            ->groupBy('t.branch_id', 't.product_id', 't.variation_id')->get()
            ->keyBy(fn ($row) => implode(':', [$row->branch_id, $row->product_id, $row->variation_id ?? 0]));
        $trend = DB::table('inventory_transactions as t')->join('products as p', 'p.id', '=', 't.product_id')
            ->where('t.store_id', $storeId)->where('p.product_type', 'finished_good')
            ->whereIn('t.branch_id', $branchIds)->where('t.quantity_change', '<', 0)
            ->whereBetween('t.transaction_date', [$start, $end])
            ->selectRaw('DATE(t.transaction_date) as day, SUM(ABS(t.quantity_change)) as units')
            ->groupByRaw('DATE(t.transaction_date)')->orderBy('day')->get();

        $rows = $stock->map(function ($item) use ($sales, $days, $branches) {
            $key = implode(':', [$item->branch_id, $item->product_id, $item->variation_id ?? 0]);
            $sold = (float) ($sales->get($key)?->units ?? 0);
            $daily = $sold / $days;
            $available = (float) $item->quantity_available;
            $point = (float) $item->reorder_point;
            $forecast = $daily * 30;
            $recommended = max(0, (int) ceil($forecast + (float) $item->safety_stock - $available - (float) $item->quantity_incoming));
            $daysCover = $daily > 0 ? round($available / $daily, 1) : null;
            $status = $available <= 0 ? 'Out of stock'
                : ($available <= $point ? 'Reorder now'
                    : ($recommended > 0 ? 'Watch'
                        : ($daysCover !== null && $daysCover > 90 ? 'Projected excess' : 'Healthy')));
            $actionKey = in_array($status, ['Out of stock', 'Reorder now', 'Watch'], true)
                ? 'create_restock'
                : ($status === 'Projected excess' ? 'review_replenishment' : 'monitor');
            return [
                'id' => $item->id, 'product_id' => $item->product_id,
                'variation_id' => $item->variation_id,
                'branch_id' => $item->branch_id,
                'branch_name' => $branches->firstWhere('id', $item->branch_id)?->name,
                'product_name' => $item->product_name, 'sku' => $item->sku,
                'available' => $available, 'incoming' => (float) $item->quantity_incoming,
                'reorder_point' => $point, 'sold' => $sold,
                'forecast_30_days' => round($forecast, 1), 'recommended' => $recommended,
                'days_cover' => $daysCover,
                'status' => $status,
                'action_key' => $actionKey,
                'action_label' => $actionKey === 'create_restock' ? 'Restock'
                    : ($actionKey === 'review_replenishment' ? 'Review' : 'Monitor'),
            ];
        })->sortBy(fn ($row) => match ($row['status']) { 'Out of stock' => 0, 'Reorder now' => 1, 'Watch' => 2, 'Projected excess' => 3, default => 4 })->values();
        if ($request->filled('search')) {
            $term = mb_strtolower((string) $request->search);
            $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower($row['product_name'].' '.$row['sku']), $term))->values();
        }
        if ($request->filled('status')) {
            $rows = $rows->where('status', $request->status)->values();
        }
        $page = max(1, (int) $request->integer('page', 1));
        $consolidated = [];
        if ($plan->allowsConsolidated($storeId)) {
            $allBranches = DB::table('branches')->where('store_id', $storeId)->get(['id', 'name', 'branch_type']);
            $storefrontIds = $allBranches->where('branch_type', '!=', 'warehouse')->pluck('id')->all();
            $warehouseIds = $allBranches->where('branch_type', 'warehouse')->pluck('id')->all();
            $demand = DB::table('inventory_transactions as t')
                ->join('products as p', 'p.id', '=', 't.product_id')
                ->where('t.store_id', $storeId)->whereIn('t.branch_id', $storefrontIds)
                ->where('p.product_type', 'finished_good')->where('t.quantity_change', '<', 0)
                ->whereBetween('t.transaction_date', [$start, $end])
                ->selectRaw('t.product_id, t.variation_id, SUM(ABS(t.quantity_change)) as units')
                ->groupBy('t.product_id', 't.variation_id')->get();
            $warehouseStock = DB::table('branch_inventory')
                ->where('store_id', $storeId)->whereIn('branch_id', $warehouseIds)->whereNull('deleted_at')
                ->selectRaw('product_id, variation_id, SUM(quantity_available) as available')
                ->groupBy('product_id', 'variation_id')->get()
                ->keyBy(fn ($item) => $item->product_id.':'.($item->variation_id ?? 0));
            $products = DB::table('products')->whereIn('id', $demand->pluck('product_id')->unique())->get(['id', 'product_name', 'sku'])->keyBy('id');
            $consolidated = $demand->map(function ($item) use ($days, $warehouseStock, $products) {
                $available = (float) ($warehouseStock->get($item->product_id.':'.($item->variation_id ?? 0))?->available ?? 0);
                $forecast = (int) ceil((float) $item->units / $days * 30);
                return ['product_id' => $item->product_id, 'variation_id' => $item->variation_id,
                    'product_name' => $products->get($item->product_id)?->product_name, 'sku' => $products->get($item->product_id)?->sku,
                    'storefront_units' => (float) $item->units, 'forecast_30_days' => $forecast,
                    'warehouse_available' => $available, 'gap' => max(0, $forecast - $available)];
            })->sortByDesc('gap')->values();
        }

        return response()->json(['data' => [
            'branches' => $branches, 'trend' => $trend,
            'consolidated' => $consolidated,
            'plan_features' => [
                'consolidated' => $plan->allowsConsolidated($storeId),
                'warehouse' => $plan->allowsWarehouse($storeId),
            ],
            'summary' => ['products' => $rows->count(), 'reorder_now' => $rows->whereIn('status', ['Out of stock', 'Reorder now'])->count(), 'forecast_units' => round($rows->sum('forecast_30_days')), 'recommended_units' => $rows->sum('recommended')],
            'rows' => $rows->forPage($page, 15)->values(), 'total' => $rows->count(),
        ]]);
    }

    private function productReport(Request $request, int $storeId, int $assignedBranch): JsonResponse
    {
        $user = $request->user();
        $permissions = app(\App\Services\Core\PermissionService::class);
        abort_unless(
            $permissions->userHasPermission($user, 'warehouse.reorder.view', $storeId)
                || $permissions->userHasPermission($user, 'warehouse.forecasting.manage', $storeId),
            403,
        );

        $validated = $request->validate([
            'inventory_id' => ['required', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'required_with:to_date', 'date', 'before_or_equal:today'],
            'to_date' => ['nullable', 'required_with:from_date', 'date', 'after_or_equal:from_date', 'before_or_equal:today'],
            'forecast_days' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([7, 14, 30, 60, 90])],
        ]);

        $branchId = (int) ($validated['branch_id'] ?? 0);
        if ($assignedBranch > 0) {
            abort_if($branchId > 0 && $branchId !== $assignedBranch, 403);
            $branchId = $assignedBranch;
        }

        $item = DB::table('branch_inventory as bi')
            ->join('branches as b', 'b.id', '=', 'bi.branch_id')
            ->join('products as p', 'p.id', '=', 'bi.product_id')
            ->leftJoin('product_variations as v', 'v.id', '=', 'bi.variation_id')
            ->where('bi.id', (int) $validated['inventory_id'])
            ->where('bi.store_id', $storeId)
            ->where('b.store_id', $storeId)
            ->where('b.branch_type', 'warehouse')
            ->whereNull('bi.deleted_at')
            ->whereNull('p.deleted_at')
            ->when($branchId > 0, fn ($query) => $query->where('bi.branch_id', $branchId))
            ->select([
                'bi.id as inventory_id', 'bi.store_id', 'bi.branch_id', 'bi.product_id', 'bi.variation_id',
                'bi.quantity_available', 'bi.quantity_incoming', 'bi.reorder_point as inventory_reorder_point',
                'bi.reorder_quantity as inventory_reorder_quantity', 'bi.safety_stock as inventory_safety_stock',
                'p.product_name', 'p.sku', 'p.product_type', 'p.unit_of_measurement', 'b.name as branch_name',
                'v.variation_name',
            ])
            ->selectRaw('COALESCE(v.variation_sku, p.sku) as item_sku')
            ->first();

        abort_unless($item, 404, 'Warehouse inventory item not found.');

        $rule = DB::table('reorder_rules')
            ->where('branch_id', $item->branch_id)
            ->where('product_id', $item->product_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        $end = isset($validated['to_date'])
            ? \Carbon\Carbon::parse($validated['to_date'])->endOfDay()
            : now()->endOfDay();
        $start = isset($validated['from_date'])
            ? \Carbon\Carbon::parse($validated['from_date'])->startOfDay()
            : $end->copy()->subDays(29)->startOfDay();
        abort_if($start->diffInDays($end->copy()->startOfDay()) + 1 > 366, 422, 'Choose a date range of up to one year.');

        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $forecastDays = (int) ($validated['forecast_days'] ?? 30);
        $readDailyDemand = function ($rangeStart, $rangeEnd) use ($item, $storeId): array {
            $daily = [];
            $variationFilter = static function ($query, string $column, $variationId) {
                return $variationId
                    ? $query->where($column, $variationId)
                    : $query->whereNull($column);
            };

            $pos = DB::table('sales_pos_order_items as item')
                ->join('sales_pos_orders as orders', 'orders.id', '=', 'item.order_id')
                ->where('orders.store_id', $storeId)
                ->where('orders.branch_id', $item->branch_id)
                ->where('orders.status', 'completed')
                ->where('item.product_id', $item->product_id)
                ->whereBetween('orders.created_at', [$rangeStart, $rangeEnd]);
            $variationFilter($pos, 'item.variation_id', $item->variation_id);
            foreach ($pos->selectRaw('DATE(orders.created_at) as demand_day, SUM(item.quantity) as units')
                ->groupByRaw('DATE(orders.created_at)')->get() as $row) {
                $date = (string) $row->demand_day;
                $daily[$date] = ($daily[$date] ?? 0) + (float) $row->units;
            }

            $online = DB::table('ecommerce_order_items as item')
                ->join('ecommerce_orders as orders', 'orders.id', '=', 'item.order_id')
                ->join('branch_inventory as inventory', 'inventory.id', '=', 'item.branch_inventory_id')
                ->where('orders.store_id', $storeId)
                ->where('orders.assigned_branch_id', $item->branch_id)
                ->whereIn('orders.status', ['delivered', 'completed'])
                ->where('orders.payment_status', '!=', 'refunded')
                ->where('inventory.store_id', $storeId)
                ->where('inventory.branch_id', $item->branch_id)
                ->where('inventory.id', $item->inventory_id)
                ->whereNull('inventory.deleted_at')
                ->whereRaw('COALESCE(orders.placed_at, orders.created_at) BETWEEN ? AND ?', [$rangeStart, $rangeEnd]);
            $variationFilter($online, 'inventory.variation_id', $item->variation_id);
            foreach ($online->selectRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) as demand_day, SUM(item.quantity) as units')
                ->groupByRaw('DATE(COALESCE(orders.placed_at, orders.created_at))')->get() as $row) {
                $date = (string) $row->demand_day;
                $daily[$date] = ($daily[$date] ?? 0) + (float) $row->units;
            }

            $returns = DB::table('ecommerce_order_returns as returns')
                ->join('ecommerce_order_items as item', 'item.id', '=', 'returns.order_item_id')
                ->join('ecommerce_orders as orders', 'orders.id', '=', 'returns.order_id')
                ->join('branch_inventory as inventory', 'inventory.id', '=', 'item.branch_inventory_id')
                ->where('orders.store_id', $storeId)
                ->where('orders.assigned_branch_id', $item->branch_id)
                ->where('inventory.store_id', $storeId)
                ->where('inventory.branch_id', $item->branch_id)
                ->where('inventory.id', $item->inventory_id)
                ->whereNull('inventory.deleted_at')
                ->whereIn('returns.status', ['received', 'return_received', 'refund_pending', 'refunded', 'replaced'])
                ->whereRaw('COALESCE(orders.placed_at, orders.created_at) BETWEEN ? AND ?', [$rangeStart, $rangeEnd]);
            $variationFilter($returns, 'inventory.variation_id', $item->variation_id);
            foreach ($returns->selectRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) as demand_day, SUM(returns.requested_quantity) as units')
                ->groupByRaw('DATE(COALESCE(orders.placed_at, orders.created_at))')->get() as $row) {
                $date = (string) $row->demand_day;
                $daily[$date] = max(0, ($daily[$date] ?? 0) - (float) $row->units);
            }

            return $daily;
        };

        $dailyDemand = $readDailyDemand($start, $end);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $start->copy()->subDays($days);
        $previousDemand = $readDailyDemand($previousStart, $previousEnd);
        $series = [];
        for ($cursor = $start->copy()->startOfDay(); $cursor->lte($end); $cursor->addDay()) {
            $day = $cursor->toDateString();
            $series[] = ['date' => $day, 'units' => round((float) ($dailyDemand[$day] ?? 0), 2)];
        }

        $unitsSold = array_sum($dailyDemand);
        $previousUnits = array_sum($previousDemand);
        $dailyAverage = $unitsSold / max(1, $days);
        $forecastUnits = $dailyAverage * $forecastDays;
        $available = (float) $item->quantity_available;
        $incoming = (float) $item->quantity_incoming;
        $reorderPoint = (float) ($rule->reorder_point ?? $item->inventory_reorder_point ?? 0);
        $refillQuantity = (float) ($rule->reorder_quantity ?? $item->inventory_reorder_quantity ?? 0);
        $safetyStock = (float) ($rule->safety_stock ?? $item->inventory_safety_stock ?? 0);
        $maximumStock = (float) ($rule->maximum_stock ?? 0);
        $ruleTargetStock = $maximumStock > 0
            ? $maximumStock
            : ($refillQuantity > 0 ? $available + $refillQuantity : $reorderPoint * 2);
        $ruleSuggestedQuantity = max(0, (int) ceil($ruleTargetStock - $available));
        $changePercent = $previousUnits > 0 ? round((($unitsSold - $previousUnits) / $previousUnits) * 100, 1) : null;

        return response()->json(['data' => [
            'product' => [
                'inventory_id' => (int) $item->inventory_id,
                'product_id' => (int) $item->product_id,
                'variation_id' => $item->variation_id ? (int) $item->variation_id : null,
                'name' => $item->product_name,
                'variation_name' => $item->variation_name,
                'sku' => $item->item_sku,
                'product_type' => $item->product_type,
                'unit' => $item->unit_of_measurement,
                'branch_id' => (int) $item->branch_id,
                'branch_name' => $item->branch_name,
            ],
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString(), 'days' => $days],
            'settings' => [
                'rule_source' => $rule ? 'Configured reorder rule' : 'Branch inventory fallback',
                'basis_type' => $rule->basis_type ?? 'reorder_point',
                'reorder_point' => $reorderPoint,
                'refill_quantity' => $refillQuantity,
                'maximum_stock' => $maximumStock > 0 ? $maximumStock : null,
                'safety_stock' => $safetyStock,
                'lead_time_days' => $rule->lead_time_days ?? null,
                'review_period_days' => $rule->review_period_days ?? null,
                'average_daily_demand' => $rule->avg_daily_demand ?? null,
            ],
            'metrics' => [
                'units_sold' => round($unitsSold, 2),
                'previous_units_sold' => round($previousUnits, 2),
                'change_percent' => $changePercent,
                'average_daily_demand' => round($dailyAverage, 3),
                'forecast_days' => $forecastDays,
                'forecast_units' => round($forecastUnits, 2),
                'available' => $available,
                'incoming' => $incoming,
                'days_cover' => $dailyAverage > 0 ? round($available / $dailyAverage, 1) : null,
                'projected_stock' => round($available + $incoming - $forecastUnits, 2),
                'suggested_replenishment' => max(0, (int) ceil($forecastUnits + $safetyStock - $available - $incoming)),
                'rule_target_stock' => $ruleTargetStock,
                'rule_suggested_quantity' => $ruleSuggestedQuantity,
                'reorder_point' => $reorderPoint,
            ],
            'series' => $series,
            'demand_source' => 'Completed POS and delivered e-commerce sales, net of recorded e-commerce returns.',
        ]]);
    }
}
