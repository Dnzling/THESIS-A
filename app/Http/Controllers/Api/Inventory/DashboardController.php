<?php
// backend/app/Http/Controllers/Api/Inventory/DashboardController.php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockAlert;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function hasGlobalAccess(): bool
    {
        $roleName = strtolower(auth()->user()?->role?->name ?? '');
        return in_array($roleName, ['super_admin', 'owner'], true);
    }

    private function getUserContext(Request $request): array
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?? 0);
        $branchId = (int) ($user?->branch_id ?? 0);

        if ($user && ($storeId === 0 || $branchId === 0)) {
            $employee = Employee::query()
                ->where('user_id', $user->id)
                ->first(['store_id', 'branch_id']);

            $storeId = $storeId ?: (int) ($employee?->store_id ?? 0);
            $branchId = $branchId ?: (int) ($employee?->branch_id ?? 0);
        }

        return [
            'store_id' => $storeId,
            'branch_id' => $branchId,
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'date_range' => 'nullable|in:today,week,month,year',
        ]);

        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $request->branch_id ?: $context['branch_id'];
        $dateRange = $request->date_range ?? 'month';

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'inventory' => [
                        'total_items' => 0,
                        'in_stock' => 0,
                        'low_stock' => 0,
                        'out_of_stock' => 0,
                        'total_quantity' => 0,
                        'total_available' => 0,
                        'total_reserved' => 0,
                        'total_damaged' => 0,
                        'total_value' => 0.0,
                    ],
                    'alerts' => [
                        'total' => 0,
                        'active' => 0,
                        'acknowledged' => 0,
                        'resolved' => 0,
                        'low_stock' => 0,
                        'out_of_stock' => 0,
                        'overstock' => 0,
                    ],
                    'adjustments' => [
                        'total' => 0,
                        'pending_approvals' => 0,
                        'approved' => 0,
                        'applied' => 0,
                    ],
                    'transfers' => [
                        'total' => 0,
                        'pending' => 0,
                        'in_transit' => 0,
                        'completed' => 0,
                        'total_goods_value' => 0.0,
                        'total_transfer_cost' => 0.0,
                    ],
                    'recent_transactions' => [],
                    'top_moving_products' => [],
                    'transaction_trends' => [],
                    'value_by_category' => [],
                    'period' => [
                        'start_date' => $this->getStartDate($dateRange)->toDateString(),
                        'end_date' => now()->toDateString(),
                        'range' => $dateRange,
                    ],
                ],
            ]);
        }
        
        $startDate = $this->getStartDate($dateRange);
        $endDate = now();

        // Build base query
        $inventoryQuery = BranchInventory::query();
        if ($storeId > 0) {
            $inventoryQuery->where('store_id', $storeId);
        }
        if ($branchId) {
            $inventoryQuery->where('branch_id', $branchId);
        }

        // Get inventory stats
        $inventoryStats = $inventoryQuery->selectRaw('
            COUNT(*) as total_items,
            SUM(CASE WHEN stock_status = "in_stock" THEN 1 ELSE 0 END) as in_stock_count,
            SUM(CASE WHEN stock_status = "low_stock" THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN stock_status = "out_of_stock" THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(quantity_on_hand) as total_quantity,
            SUM(quantity_available) as total_available,
            SUM(quantity_reserved) as total_reserved,
            SUM(quantity_damaged) as total_damaged,
            SUM(total_value) as total_inventory_value
        ')->first();

        // Get alerts stats
        $alertsQuery = StockAlert::query();
        if ($storeId > 0) {
            $alertsQuery->whereHas('branch', fn($query) => $query->where('store_id', $storeId));
        }
        if ($branchId) {
            $alertsQuery->where('branch_id', $branchId);
        }

        $alertsStats = $alertsQuery->selectRaw('
            COUNT(*) as total_alerts,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_alerts,
            SUM(CASE WHEN status = "acknowledged" THEN 1 ELSE 0 END) as acknowledged_alerts,
            SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_alerts,
            SUM(CASE WHEN alert_type = "low_stock" THEN 1 ELSE 0 END) as low_stock_alerts,
            SUM(CASE WHEN alert_type = "out_of_stock" THEN 1 ELSE 0 END) as out_of_stock_alerts,
            SUM(CASE WHEN alert_type = "overstock" THEN 1 ELSE 0 END) as overstock_alerts
        ')->first();

        // Get adjustments stats
        $adjustmentsQuery = StockAdjustment::whereBetween('created_at', [$startDate, $endDate]);
        if ($storeId > 0) {
            $adjustmentsQuery->where('store_id', $storeId);
        }
        if ($branchId) {
            $adjustmentsQuery->where('branch_id', $branchId);
        }

        $adjustmentsStats = $adjustmentsQuery->selectRaw('
            COUNT(*) as total_adjustments,
            SUM(CASE WHEN status = "pending_approval" THEN 1 ELSE 0 END) as pending_approvals,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "applied" THEN 1 ELSE 0 END) as applied_count
        ')->first();

        // Get transfers stats
        $transfersQuery = StockTransfer::whereBetween('created_at', [$startDate, $endDate]);
        if ($storeId > 0) {
            $transfersQuery->where('store_id', $storeId);
        }
        if ($branchId) {
            $transfersQuery->where(function ($query) use ($branchId) {
                $query->where('from_branch_id', $branchId)
                      ->orWhere('to_branch_id', $branchId);
            });
        }

        $transfersStats = $transfersQuery->selectRaw('
            COUNT(*) as total_transfers,
            SUM(CASE WHEN status = "requested" THEN 1 ELSE 0 END) as pending_transfers,
            SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit_count,
            SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as completed_count,
            SUM(goods_value) as total_goods_value,
            SUM(transfer_cost) as total_transfer_cost
        ')->first();

        // Get recent transactions
        $transactionsQuery = InventoryTransaction::orderBy('transaction_date', 'desc')
            ->limit(10);

        if ($storeId > 0) {
            $transactionsQuery->where('store_id', $storeId);
        }
        
        if ($branchId) {
            $transactionsQuery->where('branch_id', $branchId);
        }

        $recentTransactions = $transactionsQuery
            ->with(['product', 'branch', 'createdBy'])
            ->get();

        // Get top moving products
        $topMovingProducts = InventoryTransaction::select('product_id')
            ->selectRaw('SUM(ABS(quantity_change)) as total_movement')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy('product_id')
            ->orderByDesc('total_movement')
            ->limit(5)
            ->with('product')
            ->get();

        // Get transaction trends (last 7 days)
        $transactionTrends = InventoryTransaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(ABS(quantity_change)) as total_quantity')
            )
            ->whereBetween('transaction_date', [now()->subDays(7), now()])
            ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get stock value by category
        $valueByCategory = BranchInventory::join('products', 'branch_inventory.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.category_name')
            ->selectRaw('SUM(branch_inventory.total_value) as total_value')
            ->selectRaw('SUM(branch_inventory.quantity_on_hand) as total_quantity')
            ->when($storeId > 0, fn($q) => $q->where('branch_inventory.store_id', $storeId))
            ->when($branchId, fn($q) => $q->where('branch_inventory.branch_id', $branchId))
            ->groupBy('categories.id', 'categories.category_name')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'inventory' => [
                    'total_items' => (int) $inventoryStats->total_items,
                    'in_stock' => (int) $inventoryStats->in_stock_count,
                    'low_stock' => (int) $inventoryStats->low_stock_count,
                    'out_of_stock' => (int) $inventoryStats->out_of_stock_count,
                    'total_quantity' => (int) $inventoryStats->total_quantity,
                    'total_available' => (int) $inventoryStats->total_available,
                    'total_reserved' => (int) $inventoryStats->total_reserved,
                    'total_damaged' => (int) $inventoryStats->total_damaged,
                    'total_value' => (float) $inventoryStats->total_inventory_value,
                ],
                'alerts' => [
                    'total' => (int) $alertsStats->total_alerts,
                    'active' => (int) $alertsStats->active_alerts,
                    'acknowledged' => (int) $alertsStats->acknowledged_alerts,
                    'resolved' => (int) $alertsStats->resolved_alerts,
                    'low_stock' => (int) $alertsStats->low_stock_alerts,
                    'out_of_stock' => (int) $alertsStats->out_of_stock_alerts,
                    'overstock' => (int) $alertsStats->overstock_alerts,
                ],
                'adjustments' => [
                    'total' => (int) $adjustmentsStats->total_adjustments,
                    'pending_approvals' => (int) $adjustmentsStats->pending_approvals,
                    'approved' => (int) $adjustmentsStats->approved_count,
                    'applied' => (int) $adjustmentsStats->applied_count,
                ],
                'transfers' => [
                    'total' => (int) $transfersStats->total_transfers,
                    'pending' => (int) $transfersStats->pending_transfers,
                    'in_transit' => (int) $transfersStats->in_transit_count,
                    'completed' => (int) $transfersStats->completed_count,
                    'total_goods_value' => (float) $transfersStats->total_goods_value,
                    'total_transfer_cost' => (float) $transfersStats->total_transfer_cost,
                ],
                'recent_transactions' => $recentTransactions,
                'top_moving_products' => $topMovingProducts,
                'transaction_trends' => $transactionTrends,
                'value_by_category' => $valueByCategory,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'range' => $dateRange,
                ],
            ],
        ]);
    }

    /**
     * Get summary cards data
     */
    public function getSummaryCards(Request $request)
    {
        $context = $this->getUserContext($request);
        $storeId = $context['store_id'];
        $branchId = $request->branch_id ?: $context['branch_id'];

        if (!$this->hasGlobalAccess() && $storeId <= 0 && $branchId <= 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'title' => 'Total Items',
                        'value' => 0,
                        'icon' => 'pi pi-box',
                        'color' => 'blue',
                        'subtitle' => '₱0.00',
                    ],
                    [
                        'title' => 'In Stock',
                        'value' => 0,
                        'icon' => 'pi pi-check-circle',
                        'color' => 'green',
                        'subtitle' => '0 low stock alerts',
                    ],
                    [
                        'title' => 'Low Stock',
                        'value' => 0,
                        'icon' => 'pi pi-exclamation-triangle',
                        'color' => 'orange',
                        'subtitle' => '0 out of stock',
                    ],
                    [
                        'title' => 'Out of Stock',
                        'value' => 0,
                        'icon' => 'pi pi-times-circle',
                        'color' => 'red',
                        'subtitle' => '0 active alerts',
                    ],
                ],
            ]);
        }

        $query = BranchInventory::query();
        if ($storeId > 0) {
            $query->where('store_id', $storeId);
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_items,
            SUM(CASE WHEN stock_status = "in_stock" THEN 1 ELSE 0 END) as in_stock,
            SUM(CASE WHEN stock_status = "low_stock" THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock_status = "out_of_stock" THEN 1 ELSE 0 END) as out_of_stock,
            SUM(total_value) as total_value
        ')->first();

        $activeAlerts = StockAlert::where('status', 'active')
            ->when($storeId > 0, fn($q) => $q->whereHas('branch', fn($sub) => $sub->where('store_id', $storeId)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                [
                    'title' => 'Total Items',
                    'value' => (int) $summary->total_items,
                    'icon' => 'pi pi-box',
                    'color' => 'blue',
                    'subtitle' => '₱' . number_format($summary->total_value, 2),
                ],
                [
                    'title' => 'In Stock',
                    'value' => (int) $summary->in_stock,
                    'icon' => 'pi pi-check-circle',
                    'color' => 'green',
                    'percentage' => $summary->total_items > 0 
                        ? round(($summary->in_stock / $summary->total_items) * 100, 1) 
                        : 0,
                ],
                [
                    'title' => 'Low Stock',
                    'value' => (int) $summary->low_stock,
                    'icon' => 'pi pi-exclamation-triangle',
                    'color' => 'orange',
                    'clickable' => true,
                    'route' => '/inventory/items?filter=low_stock',
                ],
                [
                    'title' => 'Out of Stock',
                    'value' => (int) $summary->out_of_stock,
                    'icon' => 'pi pi-times-circle',
                    'color' => 'red',
                    'clickable' => true,
                    'route' => '/inventory/items?filter=out_of_stock',
                ],
                [
                    'title' => 'Active Alerts',
                    'value' => $activeAlerts,
                    'icon' => 'pi pi-bell',
                    'color' => 'red',
                    'clickable' => true,
                    'route' => '/inventory/alerts',
                ],
            ],
        ]);
    }

    /**
     * Helper to get start date based on range
     */
    private function getStartDate(string $range): Carbon
    {
        return match($range) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}
