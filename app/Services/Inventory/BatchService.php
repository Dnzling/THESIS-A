<?php
// backend/app/Services/Inventory/BatchService.php

namespace App\Services\Inventory;

use App\Models\Inventory\Batch;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class BatchService
{
    /**
     * Get paginated list of batches with filters
     */
    public function getBatches(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Batch::with(['product', 'branch', 'warehouseLocation']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['quality_status'])) {
            $query->where('quality_status', $filters['quality_status']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('warehouse_location_id', $filters['location_id']);
        }

        if (isset($filters['expiring_soon'])) {
            $days = $filters['expiring_soon'] ?? 30;
            $query->expiringSoon($days);
        }

        if (isset($filters['best_before_soon'])) {
            $days = $filters['best_before_soon'] ?? 30;
            $query->bestBeforeSoon($days);
        }

        if (isset($filters['has_stock'])) {
            if ($filters['has_stock']) {
                $query->hasAvailableStock();
            }
        }

        // Search by batch number or product name
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('batch_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('product', function ($pq) use ($filters) {
                      $pq->where('name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        // Order by production date and creation date
        $query->orderBy('production_date', 'desc')
              ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new batch
     */
    public function createBatch(array $data): Batch
    {
        DB::beginTransaction();
        try {
            // Set default values
            $data['status'] = $data['status'] ?? 'active';
            $data['quality_status'] = $data['quality_status'] ?? 'pending';
            $data['quantity_available'] = $data['quantity_available'] ?? $data['quantity_produced'];

            $batch = Batch::create($data);

            DB::commit();
            return $batch->load(['product', 'branch', 'warehouseLocation']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing batch
     */
    public function updateBatch(Batch $batch, array $data): Batch
    {
        DB::beginTransaction();
        try {
            $batch->update($data);

            DB::commit();
            return $batch->fresh(['product', 'branch', 'warehouseLocation']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reserve stock from a batch
     */
    public function reserveStock(Batch $batch, int $quantity): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->reserveStock($quantity);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Unreserve stock from a batch
     */
    public function unreserveStock(Batch $batch, int $quantity): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->unreserveStock($quantity);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sell stock from a batch
     */
    public function sellStock(Batch $batch, int $quantity): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->sellStock($quantity);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Return stock to a batch
     */
    public function returnStock(Batch $batch, int $quantity): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->returnStock($quantity);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark stock as damaged in a batch
     */
    public function markAsDamaged(Batch $batch, int $quantity): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->markAsDamaged($quantity);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Move batch to a different location
     */
    public function moveToLocation(Batch $batch, ?int $locationId, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->moveToLocation($locationId, $notes);

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve batch quality
     */
    public function approveQuality(Batch $batch): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->approveQuality();

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject batch quality
     */
    public function rejectQuality(Batch $batch): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->rejectQuality();

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Quarantine batch
     */
    public function quarantineBatch(Batch $batch): bool
    {
        DB::beginTransaction();
        try {
            $result = $batch->quarantine();

            if ($result) {
                DB::commit();
                return true;
            }

            DB::rollBack();
            return false;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Bulk import batches
     */
    public function bulkImport(array $batchesData): array
    {
        $imported = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($batchesData as $index => $data) {
                try {
                    $batch = $this->createBatch($data);
                    $imported[] = $batch;
                } catch (Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'batch_number' => $data['batch_number'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($errors)) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return [
                'imported' => $imported,
                'errors' => $errors,
                'total_imported' => count($imported),
                'total_errors' => count($errors),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get batch statistics
     */
    public function getBatchStats(?int $branchId = null): array
    {
        $query = Batch::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_batches,
            SUM(quantity_produced) as total_produced,
            SUM(quantity_available) as total_available,
            SUM(quantity_sold) as total_sold,
            SUM(quantity_reserved) as total_reserved,
            SUM(quantity_damaged) as total_damaged,
            SUM(quantity_returned) as total_returned,
            SUM(quantity_produced * unit_cost) as total_cost_value,
            SUM(quantity_available * unit_price) as total_available_value,
            SUM(quantity_sold * unit_price) as total_sold_value,
            COUNT(CASE WHEN status = "active" THEN 1 END) as active_batches,
            COUNT(CASE WHEN status = "expired" THEN 1 END) as expired_batches,
            COUNT(CASE WHEN status = "depleted" THEN 1 END) as depleted_batches,
            COUNT(CASE WHEN quality_status = "approved" THEN 1 END) as approved_quality,
            COUNT(CASE WHEN quality_status = "pending" THEN 1 END) as pending_quality,
            COUNT(CASE WHEN quality_status = "rejected" THEN 1 END) as rejected_quality,
            COUNT(CASE WHEN quality_status = "quarantined" THEN 1 END) as quarantined_quality,
            COUNT(CASE WHEN expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiry_date > NOW() THEN 1 END) as expiring_soon,
            COUNT(CASE WHEN best_before_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND best_before_date > NOW() THEN 1 END) as best_before_soon
        ')->first();

        $profit = ($stats->total_sold_value ?? 0) - ($stats->total_cost_value ?? 0);

        return [
            'total_batches' => (int) $stats->total_batches,
            'production_summary' => [
                'total_produced' => (int) $stats->total_produced,
                'total_available' => (int) $stats->total_available,
                'total_sold' => (int) $stats->total_sold,
                'total_reserved' => (int) $stats->total_reserved,
                'total_damaged' => (int) $stats->total_damaged,
                'total_returned' => (int) $stats->total_returned,
            ],
            'financial_summary' => [
                'total_cost_value' => round((float) $stats->total_cost_value, 2),
                'total_available_value' => round((float) $stats->total_available_value, 2),
                'total_sold_value' => round((float) $stats->total_sold_value, 2),
                'total_profit' => round((float) $profit, 2),
            ],
            'status_breakdown' => [
                'active' => (int) $stats->active_batches,
                'expired' => (int) $stats->expired_batches,
                'depleted' => (int) $stats->depleted_batches,
            ],
            'quality_breakdown' => [
                'approved' => (int) $stats->approved_quality,
                'pending' => (int) $stats->pending_quality,
                'rejected' => (int) $stats->rejected_quality,
                'quarantined' => (int) $stats->quarantined_quality,
            ],
            'alerts' => [
                'expiring_soon' => (int) $stats->expiring_soon,
                'best_before_soon' => (int) $stats->best_before_soon,
            ],
        ];
    }

    /**
     * Get expiring batches
     */
    public function getExpiringBatches(int $daysAhead = 30, ?int $branchId = null): Collection
    {
        $query = Batch::with(['product', 'branch'])
                     ->expiringSoon($daysAhead);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    /**
     * Get batches reaching best before date
     */
    public function getBestBeforeBatches(int $daysAhead = 30, ?int $branchId = null): Collection
    {
        $query = Batch::with(['product', 'branch'])
                     ->bestBeforeSoon($daysAhead);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('best_before_date')->get();
    }

    /**
     * Generate next batch number
     */
    public function generateNextBatchNumber(?string $prefix = null, ?int $productId = null): string
    {
        $prefix = $prefix ?? 'BATCH';

        // Get the last batch number with this prefix
        $lastBatch = Batch::where('batch_number', 'like', $prefix . '%')
                         ->when($productId, fn($q) => $q->where('product_id', $productId))
                         ->orderBy('batch_number', 'desc')
                         ->first();

        if ($lastBatch) {
            // Extract the numeric part
            $numericPart = (int) substr($lastBatch->batch_number, strlen($prefix));
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if batch number exists
     */
    public function batchNumberExists(string $batchNumber, ?int $excludeId = null): bool
    {
        $query = Batch::where('batch_number', $batchNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get batches by product
     */
    public function getBatchesByProduct(int $productId, ?int $branchId = null): Collection
    {
        $query = Batch::where('product_id', $productId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->with(['branch', 'warehouseLocation'])->get();
    }

    /**
     * Transfer batches between branches
     */
    public function transferBatches(array $batchIds, int $toBranchId, ?int $toLocationId = null, ?string $notes = null): array
    {
        $batches = Batch::whereIn('id', $batchIds)->get();

        $transferred = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($batches as $batch) {
                if ($batch->isActive() && $batch->hasAvailableStock()) {
                    $batch->update([
                        'branch_id' => $toBranchId,
                        'warehouse_location_id' => $toLocationId,
                        'notes' => $notes ? ($batch->notes ? $batch->notes . "\nTransferred: " . $notes : "Transferred: " . $notes) : $batch->notes,
                    ]);
                    $transferred[] = $batch->id;
                } else {
                    $failed[] = $batch->id;
                }
            }

            DB::commit();

            return [
                'transferred' => $transferred,
                'failed' => $failed,
                'total_transferred' => count($transferred),
                'total_failed' => count($failed),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update batch status based on expiry and stock levels
     */
    public function updateBatchStatuses(): array
    {
        $updated = 0;

        // Mark expired batches
        $expiredBatches = Batch::where('status', 'active')
                              ->where('expiry_date', '<', now())
                              ->get();

        foreach ($expiredBatches as $batch) {
            $batch->update(['status' => 'expired']);
            $updated++;
        }

        // Mark depleted batches
        $depletedBatches = Batch::where('status', 'active')
                               ->where('quantity_available', '<=', 0)
                               ->get();

        foreach ($depletedBatches as $batch) {
            $batch->update(['status' => 'depleted']);
            $updated++;
        }

        return [
            'expired_marked' => $expiredBatches->count(),
            'depleted_marked' => $depletedBatches->count(),
            'total_updated' => $updated,
        ];
    }
}