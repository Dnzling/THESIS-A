<?php
// backend/app/Services/Inventory/SerialNumberService.php

namespace App\Services\Inventory;

use App\Models\Inventory\SerialNumber;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class SerialNumberService
{
    /**
     * Get paginated list of serial numbers with filters
     */
    public function getSerialNumbers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SerialNumber::with(['product', 'branch', 'warehouseLocation']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['condition'])) {
            $query->where('condition', $filters['condition']);
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

        if (isset($filters['warranty_status'])) {
            if ($filters['warranty_status'] === 'active') {
                $query->warrantyActive();
            } elseif ($filters['warranty_status'] === 'expired') {
                $query->warrantyExpired();
            }
        }

        // Search by serial number or product name
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('serial_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('product', function ($pq) use ($filters) {
                      $pq->where('name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        // Order by creation date
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new serial number
     */
    public function createSerialNumber(array $data): SerialNumber
    {
        DB::beginTransaction();
        try {
            // Set default values
            $data['status'] = $data['status'] ?? 'available';
            $data['condition'] = $data['condition'] ?? 'new';

            $serialNumber = SerialNumber::create($data);

            DB::commit();
            return $serialNumber->load(['product', 'branch', 'warehouseLocation']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing serial number
     */
    public function updateSerialNumber(SerialNumber $serialNumber, array $data): SerialNumber
    {
        DB::beginTransaction();
        try {
            $serialNumber->update($data);

            DB::commit();
            return $serialNumber->fresh(['product', 'branch', 'warehouseLocation']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sell a serial number
     */
    public function sellSerialNumber(SerialNumber $serialNumber, float $sellingPrice, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->sell($sellingPrice, $notes);

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
     * Reserve a serial number
     */
    public function reserveSerialNumber(SerialNumber $serialNumber, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->reserve($notes);

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
     * Unreserve a serial number
     */
    public function unreserveSerialNumber(SerialNumber $serialNumber, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->unreserve($notes);

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
     * Mark serial number as damaged
     */
    public function markAsDamaged(SerialNumber $serialNumber, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->markAsDamaged($notes);

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
     * Return a serial number
     */
    public function returnSerialNumber(SerialNumber $serialNumber, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->return($notes);

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
     * Move serial number to a different location
     */
    public function moveToLocation(SerialNumber $serialNumber, ?int $locationId, ?string $notes = null): bool
    {
        DB::beginTransaction();
        try {
            $result = $serialNumber->moveToLocation($locationId, $notes);

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
     * Bulk import serial numbers
     */
    public function bulkImport(array $serialNumbersData): array
    {
        $imported = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($serialNumbersData as $index => $data) {
                try {
                    $serialNumber = $this->createSerialNumber($data);
                    $imported[] = $serialNumber;
                } catch (Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'serial_number' => $data['serial_number'] ?? 'N/A',
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
     * Get serial number statistics
     */
    public function getSerialNumberStats(?int $branchId = null): array
    {
        $query = SerialNumber::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_serials,
            SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_count,
            SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as sold_count,
            SUM(CASE WHEN status = "reserved" THEN 1 ELSE 0 END) as reserved_count,
            SUM(CASE WHEN status = "damaged" THEN 1 ELSE 0 END) as damaged_count,
            SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned_count,
            SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit_count,
            SUM(CASE WHEN condition = "new" THEN 1 ELSE 0 END) as new_condition_count,
            SUM(CASE WHEN condition = "used" THEN 1 ELSE 0 END) as used_condition_count,
            SUM(CASE WHEN condition = "refurbished" THEN 1 ELSE 0 END) as refurbished_condition_count,
            SUM(CASE WHEN condition = "damaged" THEN 1 ELSE 0 END) as damaged_condition_count,
            SUM(purchase_price) as total_purchase_value,
            SUM(selling_price) as total_selling_value,
            AVG(purchase_price) as avg_purchase_price,
            AVG(selling_price) as avg_selling_price,
            SUM(CASE WHEN warranty_expiry > NOW() THEN 1 ELSE 0 END) as active_warranties,
            SUM(CASE WHEN warranty_expiry < NOW() AND warranty_expiry IS NOT NULL THEN 1 ELSE 0 END) as expired_warranties
        ')->first();

        $profit = ($stats->total_selling_value ?? 0) - ($stats->total_purchase_value ?? 0);

        return [
            'total_serials' => (int) $stats->total_serials,
            'status_breakdown' => [
                'available' => (int) $stats->available_count,
                'sold' => (int) $stats->sold_count,
                'reserved' => (int) $stats->reserved_count,
                'damaged' => (int) $stats->damaged_count,
                'returned' => (int) $stats->returned_count,
                'in_transit' => (int) $stats->in_transit_count,
            ],
            'condition_breakdown' => [
                'new' => (int) $stats->new_condition_count,
                'used' => (int) $stats->used_condition_count,
                'refurbished' => (int) $stats->refurbished_condition_count,
                'damaged' => (int) $stats->damaged_condition_count,
            ],
            'financial_summary' => [
                'total_purchase_value' => round((float) $stats->total_purchase_value, 2),
                'total_selling_value' => round((float) $stats->total_selling_value, 2),
                'total_profit' => round((float) $profit, 2),
                'avg_purchase_price' => round((float) $stats->avg_purchase_price, 2),
                'avg_selling_price' => round((float) $stats->avg_selling_price, 2),
            ],
            'warranty_summary' => [
                'active_warranties' => (int) $stats->active_warranties,
                'expired_warranties' => (int) $stats->expired_warranties,
            ],
        ];
    }

    /**
     * Get expiring warranties
     */
    public function getExpiringWarranties(int $daysAhead = 30, ?int $branchId = null): Collection
    {
        $query = SerialNumber::with(['product', 'branch'])
                           ->whereNotNull('warranty_expiry')
                           ->where('warranty_expiry', '>', now())
                           ->where('warranty_expiry', '<=', now()->addDays($daysAhead));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('warranty_expiry')->get();
    }

    /**
     * Get expired warranties
     */
    public function getExpiredWarranties(?int $branchId = null): Collection
    {
        $query = SerialNumber::with(['product', 'branch'])
                           ->whereNotNull('warranty_expiry')
                           ->where('warranty_expiry', '<', now());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('warranty_expiry', 'desc')->get();
    }

    /**
     * Generate next serial number
     */
    public function generateNextSerialNumber(?string $prefix = null, ?int $productId = null): string
    {
        $prefix = $prefix ?? 'SN';

        // Get the last serial number with this prefix
        $lastSerial = SerialNumber::where('serial_number', 'like', $prefix . '%')
                                ->when($productId, fn($q) => $q->where('product_id', $productId))
                                ->orderBy('serial_number', 'desc')
                                ->first();

        if ($lastSerial) {
            // Extract the numeric part
            $numericPart = (int) substr($lastSerial->serial_number, strlen($prefix));
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if serial number exists
     */
    public function serialNumberExists(string $serialNumber, ?int $excludeId = null): bool
    {
        $query = SerialNumber::where('serial_number', $serialNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get serial numbers by product
     */
    public function getSerialNumbersByProduct(int $productId, ?int $branchId = null): Collection
    {
        $query = SerialNumber::where('product_id', $productId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->with(['branch', 'warehouseLocation'])->get();
    }

    /**
     * Transfer serial numbers between branches
     */
    public function transferSerialNumbers(array $serialNumberIds, int $toBranchId, ?int $toLocationId = null, ?string $notes = null): array
    {
        $serialNumbers = SerialNumber::whereIn('id', $serialNumberIds)->get();

        $transferred = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($serialNumbers as $serialNumber) {
                if ($serialNumber->status === 'available' || $serialNumber->status === 'reserved') {
                    $serialNumber->update([
                        'branch_id' => $toBranchId,
                        'warehouse_location_id' => $toLocationId,
                        'status' => 'in_transit',
                        'notes' => $notes ? ($serialNumber->notes ? $serialNumber->notes . "\nTransferred: " . $notes : "Transferred: " . $notes) : $serialNumber->notes,
                    ]);
                    $transferred[] = $serialNumber->id;
                } else {
                    $failed[] = $serialNumber->id;
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
}