<?php

namespace App\Services\Inventory;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\StockTransfer;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ReportingService
{
    protected const DAYS_TO_SLOW_MOVING = 90;

    /**
     * Get branch inventory summary with KPIs
     */
    public function getBranchSummary(int $storeId, int $branchId, ?int $days = null): array
    {
        $query = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId);

        $items = $query->get();

        return [
            'branch_id' => $branchId,
            'total_items' => $items->count(),
            'total_value' => (float) $items->sum('total_value'),
            'total_quantity' => $items->sum('quantity_on_hand'),
            'in_stock' => $items->where('stock_status', 'in_stock')->count(),
            'low_stock' => $items->where('stock_status', 'low_stock')->count(),
            'out_of_stock' => $items->where('stock_status', 'out_of_stock')->count(),
            'damaged_units' => $items->sum('quantity_damaged'),
            'reserved_units' => $items->sum('quantity_reserved'),
            'average_unit_cost' => $items->count() > 0 
                ? $items->avg('average_cost') 
                : 0,
            'inventory_accuracy' => $this->calculateAccuracy($storeId, $branchId),
            'stock_turnover_ratio' => $this->calculateStockTurnover($storeId, $branchId, $days),
            'sell_through_rate' => $this->calculateSellThroughRate($storeId, $branchId, $days),
        ];
    }

    /**
     * Get multi-branch consolidated view for store
     */
    public function getStoreSummary(int $storeId, ?int $days = null): array
    {
        $branches = \App\Models\Store\Branch::where('store_id', $storeId)->get();
        $summaries = [];
        $totals = [
            'total_value' => 0,
            'total_quantity' => 0,
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'damaged_units' => 0,
        ];

        foreach ($branches as $branch) {
            $summary = $this->getBranchSummary($storeId, $branch->id, $days);
            $summaries[$branch->id] = $summary;

            $totals['total_value'] += $summary['total_value'];
            $totals['total_quantity'] += $summary['total_quantity'];
            $totals['in_stock'] += $summary['in_stock'];
            $totals['low_stock'] += $summary['low_stock'];
            $totals['out_of_stock'] += $summary['out_of_stock'];
            $totals['damaged_units'] += $summary['damaged_units'];
        }

        return [
            'store_id' => $storeId,
            'branch_summaries' => $summaries,
            'totals' => $totals,
            'stock_accuracy' => $this->calculateStoreAccuracy($storeId),
            'average_stock_turnover' => $this->calculateAverageStockTurnover($storeId, $days),
        ];
    }

    /**
     * Get movement trends over period
     */
    public function getMovementTrends(int $storeId, int $branchId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $transactions = InventoryTransaction::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $trends = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayTransactions = $transactions->where('created_at', '>=', $date)
                ->where('created_at', '<', $date . ' 23:59:59');

            $trends[$date] = [
                'date' => $date,
                'additions' => $dayTransactions->where('transaction_type', 'ADD')->sum('details.quantity') ?? 0,
                'deductions' => $dayTransactions->where('transaction_type', 'DEDUCT')->sum('details.quantity') ?? 0,
                'adjustments' => $dayTransactions->where('transaction_type', 'ADJUST')->count(),
                'transfers_in' => $dayTransactions->where('transaction_type', 'TRANSFER_IN')->count(),
                'transfers_out' => $dayTransactions->where('transaction_type', 'TRANSFER_OUT')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get inventory value by category
     */
    public function getValueByCategory(int $storeId, int $branchId): array
    {
        $items = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->with('product.category')
            ->get();

        return $items
            ->groupBy('product.category.name')
            ->map(function ($categoryItems, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total_value' => (float) $categoryItems->sum('total_value'),
                    'total_items' => $categoryItems->count(),
                    'total_units' => $categoryItems->sum('quantity_on_hand'),
                    'percentage_of_total' => 0, // Calculated after
                ];
            })
            ->toArray();
    }

    /**
     * Get slow-moving items
     */
    public function getSlowMovers(int $storeId, int $branchId, int $days = 90, int $minValue = 5000): Collection
    {
        $cutoffDate = now()->subDays($days);

        $items = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('total_value', '>=', $minValue)
            ->get();

        return $items->filter(function ($item) use ($cutoffDate, $storeId, $branchId) {
            $lastMovement = InventoryTransaction::where('store_id', $storeId)
                ->where('branch_id', $branchId)
                ->where('details->product_id', $item->product_id)
                ->where('created_at', '<', $cutoffDate)
                ->latest()
                ->first();

            return !$lastMovement || $lastMovement->created_at < $cutoffDate;
        });
    }

    /**
     * Get fast-moving items
     */
    public function getFastMovers(int $storeId, int $branchId, int $days = 30, int $minQty = 50): Collection
    {
        $startDate = now()->subDays($days);

        $transactionsByProduct = InventoryTransaction::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'DEDUCT')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy('details.product_id');

        $fastMovers = collect();

        foreach ($transactionsByProduct as $productId => $transactions) {
            $totalQty = $transactions->sum('details.quantity');

            if ($totalQty >= $minQty) {
                $inventory = BranchInventory::where('store_id', $storeId)
                    ->where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->first();

                if ($inventory) {
                    $fastMovers->push([
                        'product_id' => $productId,
                        'product_name' => $inventory->product->name,
                        'units_sold' => $totalQty,
                        'current_stock' => $inventory->quantity_available,
                        'days_until_stockout' => $inventory->quantity_available > 0 
                            ? (int) ($inventory->quantity_available / ($totalQty / $days))
                            : 0,
                    ]);
                }
            }
        }

        return $fastMovers->sortByDesc('units_sold');
    }

    /**
     * Calculate inventory accuracy percentage
     */
    protected function calculateAccuracy(int $storeId, int $branchId): float
    {
        $items = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->get();

        if ($items->isEmpty()) {
            return 100.0;
        }

        $accurate = $items->filter(function ($item) {
            if (!$item->last_stock_count_date) {
                return true;
            }
            $variance = abs($item->quantity_on_hand - ($item->last_counted_quantity ?? 0));
            return $variance === 0;
        })->count();

        return ($accurate / $items->count()) * 100;
    }

    /**
     * Calculate store-wide accuracy
     */
    protected function calculateStoreAccuracy(int $storeId): float
    {
        $items = BranchInventory::where('store_id', $storeId)->get();

        if ($items->isEmpty()) {
            return 100.0;
        }

        $accurate = $items->filter(function ($item) {
            if (!$item->last_stock_count_date) {
                return true;
            }
            $variance = abs($item->quantity_on_hand - ($item->last_counted_quantity ?? 0));
            return $variance === 0;
        })->count();

        return ($accurate / $items->count()) * 100;
    }

    /**
     * Calculate stock turnover ratio (sales / average inventory)
     */
    protected function calculateStockTurnover(int $storeId, int $branchId, ?int $days = null): float
    {
        $period = $days ?? 30;
        $startDate = now()->subDays($period);

        // Calculate total sales in period
        $sales = InventoryTransaction::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'DEDUCT')
            ->where('created_at', '>=', $startDate)
            ->sum('details.quantity');

        // Calculate average inventory value
        $items = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->get();

        $avgInventory = $items->avg('total_value');

        return $avgInventory > 0 ? $sales / $avgInventory : 0;
    }

    /**
     * Calculate average stock turnover for store
     */
    protected function calculateAverageStockTurnover(int $storeId, ?int $days = null): float
    {
        $branches = \App\Models\Store\Branch::where('store_id', $storeId)->get();

        if ($branches->isEmpty()) {
            return 0;
        }

        $totals = 0;
        foreach ($branches as $branch) {
            $totals += $this->calculateStockTurnover($storeId, $branch->id, $days);
        }

        return $totals / $branches->count();
    }

    /**
     * Calculate sell-through rate
     */
    protected function calculateSellThroughRate(int $storeId, int $branchId, ?int $days = null): float
    {
        $period = $days ?? 30;
        $startDate = now()->subDays($period);

        $sold = InventoryTransaction::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'DEDUCT')
            ->where('created_at', '>=', $startDate)
            ->sum('details.quantity');

        $received = InventoryTransaction::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'ADD')
            ->where('created_at', '>=', $startDate)
            ->sum('details.quantity');

        $totalAvailable = $sold + BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->sum('quantity_on_hand');

        return $totalAvailable > 0 ? ($sold / $totalAvailable) * 100 : 0;
    }

    /**
     * Get transfer metrics
     */
    public function getTransferMetrics(int $storeId, int $branchId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $transfers = StockTransfer::where('store_id', $storeId)
            ->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            })
            ->where('created_at', '>=', $startDate)
            ->get();

        $sent = $transfers->where('from_branch_id', $branchId);
        $received = $transfers->where('to_branch_id', $branchId);

        return [
            'total_transfers' => $transfers->count(),
            'sent_transfers' => $sent->count(),
            'received_transfers' => $received->count(),
            'average_transfer_cost' => $transfers->avg('transfer_cost') ?? 0,
            'completed_transfers' => $transfers->where('status', 'received')->count(),
            'pending_transfers' => $transfers->where('status', 'pending')->count(),
            'total_units_transferred' => $transfers->sum('total_quantity'),
        ];
    }

    /**
     * Get inventory aging report
     */
    public function getAgingReport(int $storeId, int $branchId): array
    {
        $now = now();
        $items = BranchInventory::where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->get();

        return [
            'less_than_30_days' => $items->filter(function ($item) use ($now) {
                return $item->last_stock_count_date && $now->diffInDays($item->last_stock_count_date) <= 30;
            })->count(),
            'between_30_60_days' => $items->filter(function ($item) use ($now) {
                return $item->last_stock_count_date && $now->diffInDays($item->last_stock_count_date) > 30 && $now->diffInDays($item->last_stock_count_date) <= 60;
            })->count(),
            'between_60_90_days' => $items->filter(function ($item) use ($now) {
                return $item->last_stock_count_date && $now->diffInDays($item->last_stock_count_date) > 60 && $now->diffInDays($item->last_stock_count_date) <= 90;
            })->count(),
            'older_than_90_days' => $items->filter(function ($item) use ($now) {
                return $item->last_stock_count_date && $now->diffInDays($item->last_stock_count_date) > 90;
            })->count(),
            'never_counted' => $items->filter(function ($item) {
                return !$item->last_stock_count_date;
            })->count(),
        ];
    }
}
