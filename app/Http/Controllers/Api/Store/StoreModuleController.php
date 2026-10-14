<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderItem;
use App\Models\Store\StoreBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StoreModuleController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $branch = $this->branchFor($request);
        $start = now()->subDays(29)->startOfDay();
        $today = now()->startOfDay();

        $ecommerce = $this->ecommerceQuery($branch);
        $pos = Schema::hasTable('sales_pos_orders')
            ? DB::table('sales_pos_orders')->where('store_id', $branch->store_id)->where('branch_id', $branch->id)
            : null;

        $ecommerce30 = (clone $ecommerce)->where('created_at', '>=', $start);
        $ecommerceRevenue = (float) (clone $ecommerce30)->whereNotIn('status', ['cancelled', 'rejected'])->sum('total_amount');
        $ecommerceOrders = (int) (clone $ecommerce30)->count();
        $posRevenue = $pos ? (float) (clone $pos)->where('created_at', '>=', $start)->sum('total_amount') : 0;
        $posOrders = $pos ? (int) (clone $pos)->where('created_at', '>=', $start)->count() : 0;

        $inventory = DB::table('branch_inventory')->where('store_id', $branch->store_id)->where('branch_id', $branch->id);

        return response()->json(['success' => true, 'data' => [
            'branch' => $this->branchPayload($branch),
            'kpis' => [
                'sales_today' => (float) (clone $ecommerce)->where('created_at', '>=', $today)->sum('total_amount')
                    + ($pos ? (float) (clone $pos)->where('created_at', '>=', $today)->sum('total_amount') : 0),
                'orders_today' => (int) (clone $ecommerce)->where('created_at', '>=', $today)->count()
                    + ($pos ? (int) (clone $pos)->where('created_at', '>=', $today)->count() : 0),
                'revenue_30d' => $ecommerceRevenue + $posRevenue,
                'orders_30d' => $ecommerceOrders + $posOrders,
                'ecommerce_revenue_30d' => $ecommerceRevenue,
                'pos_revenue_30d' => $posRevenue,
                'active_staff' => $branch->employees()->where('status', 'active')->count(),
                'low_stock' => (clone $inventory)->where('stock_status', 'low_stock')->count(),
                'out_of_stock' => (clone $inventory)->where('stock_status', 'out_of_stock')->count(),
            ],
            'sales_trend' => $this->combinedSalesTrend($branch, 14),
            'recent_orders' => $this->recentOrders($branch),
        ]]);
    }

    public function settings(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->branchPayload($this->branchFor($request))]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $branch = $this->branchFor($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:255',
            'barangay' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_enabled' => 'sometimes|boolean',
            'geofence_radius_m' => 'nullable|integer|min:0|max:5000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        unset($validated['logo']);
        if ($request->hasFile('logo')) {
            if ($branch->logo_path) Storage::disk('public')->delete($branch->logo_path);
            $validated['logo_path'] = $request->file('logo')->store("branch-logos/{$branch->store_id}/{$branch->id}", 'public');
        }

        $branch->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Store information updated successfully.',
            'data' => $this->branchPayload($branch->fresh()),
        ]);
    }

    public function ecommerce(Request $request): JsonResponse
    {
        $branch = $this->branchFor($request);
        $days = max(7, min(90, (int) $request->integer('days', 30)));
        $start = now()->subDays($days - 1)->startOfDay();
        $query = $this->ecommerceQuery($branch)->where('created_at', '>=', $start);
        $orders = (clone $query)->count();
        $revenue = (float) (clone $query)->whereNotIn('status', ['cancelled', 'rejected'])->sum('total_amount');

        $statusRows = (clone $query)->select('status', DB::raw('COUNT(*) total'))->groupBy('status')->orderByDesc('total')->get();
        $topProducts = EcommerceOrderItem::query()
            ->join('ecommerce_orders as orders', 'orders.id', '=', 'ecommerce_order_items.order_id')
            ->where('orders.store_id', $branch->store_id)
            ->where('orders.assigned_branch_id', $branch->id)
            ->where('orders.created_at', '>=', $start)
            ->select('ecommerce_order_items.product_name', 'ecommerce_order_items.sku', DB::raw('SUM(ecommerce_order_items.quantity) quantity'), DB::raw('SUM(ecommerce_order_items.line_total) revenue'))
            ->groupBy('ecommerce_order_items.product_name', 'ecommerce_order_items.sku')
            ->orderByDesc('quantity')->limit(8)->get();

        return response()->json(['success' => true, 'data' => [
            'branch' => $this->branchPayload($branch),
            'period_days' => $days,
            'kpis' => [
                'orders' => $orders,
                'revenue' => $revenue,
                'average_order_value' => $orders ? round($revenue / $orders, 2) : 0,
                'completed_orders' => (clone $query)->whereIn('status', ['completed', 'delivered'])->count(),
                'pending_orders' => (clone $query)->whereIn('status', ['pending', 'processing', 'confirmed'])->count(),
                'cancelled_orders' => (clone $query)->whereIn('status', ['cancelled', 'rejected'])->count(),
            ],
            'revenue_trend' => $this->ecommerceTrend($branch, $days),
            'status_breakdown' => [
                'labels' => $statusRows->pluck('status')->map(fn ($value) => ucfirst(str_replace('_', ' ', $value ?: 'unknown')))->values(),
                'values' => $statusRows->pluck('total')->map(fn ($value) => (int) $value)->values(),
            ],
            'top_products' => $topProducts,
            'recent_orders' => (clone $query)->latest()->limit(10)->get(['id', 'order_number', 'shipping_name', 'status', 'payment_status', 'total_amount', 'created_at']),
        ]]);
    }

    private function branchFor(Request $request): StoreBranch
    {
        $user = $request->user()->loadMissing('employee');
        $storeId = $user->store_id ?: $user->employee?->store_id;
        $branchId = $user->branch_id ?: $user->employee?->branch_id;

        abort_unless($storeId && $branchId, 422, 'Your account is not assigned to a store branch.');

        return StoreBranch::query()->where('store_id', $storeId)->findOrFail($branchId);
    }

    private function ecommerceQuery(StoreBranch $branch): Builder
    {
        return EcommerceOrder::query()->where('store_id', $branch->store_id)->where('assigned_branch_id', $branch->id);
    }

    private function branchPayload(StoreBranch $branch): array
    {
        return [
            'id' => $branch->id, 'store_id' => $branch->store_id, 'name' => $branch->name,
            'branch_code' => $branch->branch_code, 'branch_type' => $branch->branch_type,
            'is_main_branch' => (bool) $branch->is_main_branch, 'status' => $branch->status,
            'contact_number' => $branch->contact_number, 'email' => $branch->email,
            'address' => $branch->address, 'barangay' => $branch->barangay,
            'city' => $branch->city, 'province' => $branch->province,
            'latitude' => $branch->latitude, 'longitude' => $branch->longitude,
            'geofence_enabled' => (bool) $branch->geofence_enabled,
            'geofence_radius_m' => $branch->geofence_radius_m,
            'logo_url' => $branch->logo_url,
        ];
    }

    private function combinedSalesTrend(StoreBranch $branch, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $ecommerce = $this->ecommerceQuery($branch)->where('created_at', '>=', $start)->get(['created_at', 'total_amount']);
        $pos = Schema::hasTable('sales_pos_orders')
            ? DB::table('sales_pos_orders')->where('store_id', $branch->store_id)->where('branch_id', $branch->id)->where('created_at', '>=', $start)->get(['created_at', 'total_amount'])
            : collect();
        $totals = $ecommerce->concat($pos)->groupBy(fn ($row) => Carbon::parse($row->created_at)->toDateString())->map->sum('total_amount');

        $labels = []; $values = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i); $labels[] = $date->format('M d'); $values[] = (float) ($totals[$date->toDateString()] ?? 0);
        }
        return compact('labels', 'values');
    }

    private function ecommerceTrend(StoreBranch $branch, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $totals = $this->ecommerceQuery($branch)->where('created_at', '>=', $start)->get(['created_at', 'total_amount'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->toDateString())->map->sum('total_amount');
        $labels = []; $values = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i); $labels[] = $date->format('M d'); $values[] = (float) ($totals[$date->toDateString()] ?? 0);
        }
        return compact('labels', 'values');
    }

    private function recentOrders(StoreBranch $branch): array
    {
        $ecommerce = $this->ecommerceQuery($branch)->latest()->limit(6)->get(['order_number', 'shipping_name', 'status', 'total_amount', 'created_at'])
            ->map(fn ($order) => ['channel' => 'Ecommerce', 'order_number' => $order->order_number, 'customer_name' => $order->shipping_name, 'status' => $order->status, 'total_amount' => $order->total_amount, 'created_at' => $order->created_at]);
        return $ecommerce->sortByDesc('created_at')->take(6)->values()->all();
    }
}
