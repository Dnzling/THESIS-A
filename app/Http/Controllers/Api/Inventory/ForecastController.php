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

        $days = (int) $request->integer('days', 30);
        abort_unless(in_array($days, [30, 60, 90], true), 422);
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
        $since = now()->subDays($days - 1)->startOfDay();

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
            ->where('t.quantity_change', '<', 0)->where('t.transaction_date', '>=', $since)
            ->selectRaw('t.branch_id, t.product_id, t.variation_id, SUM(ABS(t.quantity_change)) as units')
            ->groupBy('t.branch_id', 't.product_id', 't.variation_id')->get()
            ->keyBy(fn ($row) => implode(':', [$row->branch_id, $row->product_id, $row->variation_id ?? 0]));
        $trend = DB::table('inventory_transactions as t')->join('products as p', 'p.id', '=', 't.product_id')
            ->where('t.store_id', $storeId)->where('p.product_type', 'finished_good')
            ->whereIn('t.branch_id', $branchIds)->where('t.quantity_change', '<', 0)
            ->where('t.transaction_date', '>=', $since)
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
            return [
                'id' => $item->id, 'product_id' => $item->product_id,
                'branch_id' => $item->branch_id,
                'branch_name' => $branches->firstWhere('id', $item->branch_id)?->name,
                'product_name' => $item->product_name, 'sku' => $item->sku,
                'available' => $available, 'incoming' => (float) $item->quantity_incoming,
                'reorder_point' => $point, 'sold' => $sold,
                'forecast_30_days' => round($forecast, 1), 'recommended' => $recommended,
                'days_cover' => $daily > 0 ? round($available / $daily, 1) : null,
                'status' => $available <= 0 ? 'Out of stock' : ($available <= $point ? 'Reorder now' : ($recommended > 0 ? 'Watch' : 'Healthy')),
            ];
        })->sortBy(fn ($row) => match ($row['status']) { 'Out of stock' => 0, 'Reorder now' => 1, 'Watch' => 2, default => 3 })->values();
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
                ->where('t.transaction_date', '>=', $since)
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
            'consolidated' => $consolidated, 'plan_features' => ['consolidated' => $plan->allowsConsolidated($storeId)],
            'summary' => ['products' => $rows->count(), 'reorder_now' => $rows->whereIn('status', ['Out of stock', 'Reorder now'])->count(), 'forecast_units' => round($rows->sum('forecast_30_days')), 'recommended_units' => $rows->sum('recommended')],
            'rows' => $rows->forPage($page, 15)->values(), 'total' => $rows->count(),
        ]]);
    }
}
