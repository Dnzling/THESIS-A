<?php
// backend/app/Services/Inventory/StockCountService.php

namespace App\Services\Inventory;

use App\Models\Inventory\StockCount;
use App\Models\Inventory\CountSheet;
use App\Models\Inventory\BranchInventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StockCountService
{
    /**
     * Create a new stock count
     */
    public function createStockCount(array $data): StockCount
    {
        return DB::transaction(function () use ($data) {
            // Generate count number
            $countNumber = $this->generateCountNumber($data['store_id']);

            // Create the stock count
            $count = StockCount::create([
                'count_number' => $countNumber,
                'store_id' => $data['store_id'],
                'branch_id' => $data['branch_id'],
                'status' => 'pending_approval',
                'count_type' => $data['count_type'],
                'scheduled_date' => $data['scheduled_date'],
                'assigned_by' => $data['assigned_by'],
                'assigned_to' => $data['assigned_to'],
                'supervised_by' => $data['supervised_by'] ?? null,
                'warehouse_section' => $data['warehouse_section'] ?? null,
                'aisle' => $data['aisle'] ?? null,
                'rack' => $data['rack'] ?? null,
                'shelf' => $data['shelf'] ?? null,
                'category_ids' => $data['category_ids'] ?? null,
                'product_ids' => $data['product_ids'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Generate count sheets based on the count type and filters
            $this->generateCountSheets($count);

            return $count;
        });
    }

    /**
     * Update an existing stock count
     */
    public function updateStockCount(StockCount $count, array $data): StockCount
    {
        return DB::transaction(function () use ($count, $data) {
            $count->update([
                'branch_id' => $data['branch_id'],
                'count_type' => $data['count_type'],
                'scheduled_date' => $data['scheduled_date'],
                'assigned_to' => $data['assigned_to'],
                'supervised_by' => $data['supervised_by'] ?? null,
                'warehouse_section' => $data['warehouse_section'] ?? null,
                'aisle' => $data['aisle'] ?? null,
                'rack' => $data['rack'] ?? null,
                'shelf' => $data['shelf'] ?? null,
                'category_ids' => $data['category_ids'] ?? null,
                'product_ids' => $data['product_ids'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Regenerate count sheets if filters changed
            $count->countSheets()->delete();
            $this->generateCountSheets($count);

            return $count;
        });
    }

    /**
     * Start a stock count
     */
    public function startStockCount(StockCount $count): StockCount
    {
        $count->update([
            'status' => 'in_progress',
            'started_date' => now(),
        ]);

        return $count;
    }

    /**
     * Complete a stock count
     */
    public function completeStockCount(StockCount $count): StockCount
    {
        // Calculate summary statistics
        $sheets = $count->countSheets;
        $totalCounted = $sheets->whereNotNull('counted_quantity')->count();
        $withDiscrepancy = $sheets->where('count_status', 'discrepancy_found')->count();
        $totalValue = $sheets->sum('counted_total_value');
        $discrepancyValue = $sheets->sum('discrepancy_value');

        $count->update([
            'status' => 'completed',
            'completed_date' => now(),
            'total_items_counted' => $totalCounted,
            'items_with_discrepancy' => $withDiscrepancy,
            'total_value_counted' => $totalValue,
            'total_discrepancy_value' => abs($discrepancyValue),
        ]);

        return $count;
    }

    /**
     * Approve a stock count
     */
    public function approveStockCount(StockCount $count, array $data): StockCount
    {
        return DB::transaction(function () use ($count, $data) {
            // Approval of a newly created count request (manager gate before execution)
            if ($count->status === 'pending_approval') {
                $count->update([
                    'status' => 'scheduled',
                    'approved_date' => now(),
                    'approved_by' => $data['approved_by'],
                    'approval_notes' => $data['approval_notes'] ?? null,
                ]);

                return $count;
            }

            $count->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => $data['approved_by'],
                'approval_notes' => $data['approval_notes'] ?? null,
            ]);

            // Update inventory quantities based on count results
            foreach ($count->countSheets as $sheet) {
                if ($sheet->counted_quantity !== null && $sheet->hasDiscrepancy()) {
                    $inventory = $sheet->branchInventory;
                    if ($inventory) {
                        $inventory->update([
                            'quantity_on_hand' => $sheet->counted_quantity,
                            'quantity_available' => $sheet->counted_quantity,
                            'last_stock_count_date' => now(),
                            'last_counted_quantity' => $sheet->counted_quantity,
                            'last_counted_by' => $data['approved_by'],
                        ]);
                    }
                }
            }

            return $count;
        });
    }

    /**
     * Update count sheets with counted quantities
     */
    public function updateCountSheets(StockCount $count, array $counts, int $countedBy): array
    {
        $updatedSheets = [];

        foreach ($counts as $countData) {
            $sheet = CountSheet::findOrFail($countData['count_sheet_id']);

            // Ensure the sheet belongs to this count
            if ($sheet->stock_count_id !== $count->id) {
                continue;
            }

            $sheet->update([
                'counted_quantity' => $countData['counted_quantity'],
                'counted_unit_cost' => $sheet->system_unit_cost, // Use system cost
                'counted_total_value' => $countData['counted_quantity'] * $sheet->system_unit_cost,
                'counted_at' => now(),
                'counted_by' => $countedBy,
                'notes' => $countData['notes'] ?? null,
            ]);

            // Calculate discrepancy
            $sheet->calculateDiscrepancy();
            $sheet->save();

            $updatedSheets[] = $sheet->load(['product', 'countedBy']);
        }

        return $updatedSheets;
    }

    /**
     * Generate count sheets based on count type and filters
     */
    private function generateCountSheets(StockCount $count): void
    {
        $query = BranchInventory::with(['product', 'variation'])
            ->where('store_id', $count->store_id)
            ->where('branch_id', $count->branch_id)
            ->where('quantity_on_hand', '>', 0); // Only count items with stock

        // Apply filters based on count type
        switch ($count->count_type) {
            case 'partial_count':
                if ($count->warehouse_section) {
                    $query->where('warehouse_section', $count->warehouse_section);
                }
                if ($count->aisle) {
                    $query->where('aisle', $count->aisle);
                }
                if ($count->rack) {
                    $query->where('rack', $count->rack);
                }
                if ($count->shelf) {
                    $query->where('shelf', $count->shelf);
                }
                if ($count->category_ids) {
                    $query->whereHas('product', function ($q) use ($count) {
                        $q->whereIn('category_id', $count->category_ids);
                    });
                }
                if ($count->product_ids) {
                    $query->whereIn('product_id', $count->product_ids);
                }
                break;

            case 'spot_check':
                if ($count->product_ids) {
                    $query->whereIn('product_id', $count->product_ids);
                }
                // Limit to a smaller sample for spot checks
                $query->inRandomOrder()->limit(20);
                break;

            case 'cycle_count':
                // Cycle counts typically focus on high-value or fast-moving items
                if ($count->product_ids) {
                    $query->whereIn('product_id', $count->product_ids);
                } else {
                    $query->orderBy('total_value', 'desc')->limit(50);
                }
                break;

            case 'full_inventory':
            default:
                // No additional filters for full inventory
                break;
        }

        $inventoryItems = $query->get();
        $totalItems = $inventoryItems->count();
        $totalValue = $inventoryItems->sum('total_value');

        // Create count sheets
        foreach ($inventoryItems as $item) {
            CountSheet::create([
                'stock_count_id' => $count->id,
                'branch_inventory_id' => $item->id,
                'product_id' => $item->product_id,
                'variation_id' => $item->variation_id,
                'system_quantity' => $item->quantity_on_hand,
                'system_unit_cost' => $item->unit_cost ?? 0,
                'system_total_value' => $item->total_value ?? 0,
                'warehouse_section' => $item->warehouse_section,
                'aisle' => $item->aisle,
                'rack' => $item->rack,
                'shelf' => $item->shelf,
                'bin_code' => $item->bin_code,
            ]);
        }

        // Update count with expected totals
        $count->update([
            'total_items_expected' => $totalItems,
        ]);
    }

    /**
     * Suggest items for cycle count based on value and discrepancy history.
     */
    public function getCycleCountSuggestions(int $storeId, int $branchId, int $limit = 50, int $days = 90): array
    {
        $inventory = BranchInventory::with('product')
            ->where('store_id', $storeId)
            ->where('branch_id', $branchId)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        $discrepancies = CountSheet::whereHas('stockCount', function ($q) use ($storeId, $branchId, $days) {
                $q->where('store_id', $storeId)
                    ->where('branch_id', $branchId)
                    ->whereNotNull('completed_date')
                    ->where('completed_date', '>=', now()->subDays($days));
            })
            ->selectRaw('product_id, SUM(ABS(discrepancy_value)) as discrepancy_value_sum, COUNT(*) as discrepancy_count')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $items = $inventory->map(function ($item) use ($discrepancies, $days) {
            $disc = $discrepancies->get($item->product_id);
            $discValue = (float) ($disc?->discrepancy_value_sum ?? 0);
            $discCount = (int) ($disc?->discrepancy_count ?? 0);
            $value = (float) ($item->total_value ?? 0);
            $score = ($discValue * 2) + $value;

            $reasons = [];
            if ($discCount > 0) {
                $reasons[] = "Discrepancy history (₱" . number_format($discValue, 2) . " over {$days}d)";
            }
            if ($value > 0) {
                $reasons[] = "High value stock (₱" . number_format($value, 2) . ")";
            }

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->product_name,
                'sku' => $item->product?->sku,
                'current_stock' => $item->quantity_on_hand,
                'total_value' => $value,
                'score' => $score,
                'reasons' => $reasons,
            ];
        });

        $sorted = $items->sortByDesc('score')->values()->take($limit);

        return [
            'items' => $sorted->values()->all(),
            'product_ids' => $sorted->pluck('product_id')->all(),
            'limit' => $limit,
            'days' => $days,
        ];
    }

    /**
     * Auto-schedule weekly cycle counts.
     */
    public function autoScheduleCycleCounts(int $storeId, int $branchId, int $assignedBy, int $assignedTo, int $weeks = 4, int $perCount = 50, ?string $startDate = null): array
    {
        $suggestions = $this->getCycleCountSuggestions($storeId, $branchId, $weeks * $perCount);
        $chunks = array_chunk($suggestions['product_ids'], $perCount);

        $created = [];
        $start = $startDate ? Carbon::parse($startDate) : now()->addDay();

        foreach ($chunks as $index => $productIds) {
            $count = $this->createStockCount([
                'store_id' => $storeId,
                'branch_id' => $branchId,
                'count_type' => 'cycle_count',
                'scheduled_date' => $start->copy()->addWeeks($index)->toDateString(),
                'assigned_by' => $assignedBy,
                'assigned_to' => $assignedTo,
                'product_ids' => $productIds,
                'instructions' => 'Auto-scheduled cycle count',
                'notes' => 'Auto-scheduled from cycle count suggestions',
            ]);

            $created[] = $count;
        }

        return [
            'scheduled' => $created,
            'total_scheduled' => count($created),
        ];
    }

    /**
     * Generate a unique count number
     */
    private function generateCountNumber(int $storeId): string
    {
        do {
            $number = 'SC-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (StockCount::where('count_number', $number)->exists());

        return $number;
    }
}
