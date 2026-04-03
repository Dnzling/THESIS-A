<?php
// backend/app/Services/Inventory/WarehouseService.php

namespace App\Services\Inventory;

use App\Models\Inventory\Warehouse;
use App\Models\Store\Branch;
use Illuminate\Support\Facades\Log;
use Exception;

class WarehouseService
{
    /**
     * Create a new warehouse.
     */
    public function createWarehouse(array $data): Warehouse
    {
        try {
            // Generate warehouse code if not provided
            if (!isset($data['warehouse_code']) || empty($data['warehouse_code'])) {
                $data['warehouse_code'] = $this->generateWarehouseCode($data['branch_id'], $data['type']);
            }

            $warehouse = Warehouse::create($data);
            Branch::query()
                ->where('id', $warehouse->branch_id)
                ->update(['branch_type' => 'warehouse']);

            Log::info('Warehouse created successfully', [
                'warehouse_id' => $warehouse->id,
                'warehouse_code' => $warehouse->warehouse_code,
                'name' => $warehouse->name,
            ]);

            return $warehouse;

        } catch (Exception $e) {
            Log::error('Failed to create warehouse', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing warehouse.
     */
    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        try {
            $oldData = $warehouse->toArray();

            $warehouse->update($data);
            Branch::query()
                ->where('id', $warehouse->branch_id)
                ->update(['branch_type' => 'warehouse']);

            Log::info('Warehouse updated successfully', [
                'warehouse_id' => $warehouse->id,
                'warehouse_code' => $warehouse->warehouse_code,
                'changes' => array_diff_assoc($data, $oldData),
            ]);

            return $warehouse;

        } catch (Exception $e) {
            Log::error('Failed to update warehouse', [
                'warehouse_id' => $warehouse->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique warehouse code.
     */
    private function generateWarehouseCode(int $branchId, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 2)); // First 2 letters of type
        $branchCode = str_pad($branchId, 3, '0', STR_PAD_LEFT);

        $counter = 1;
        do {
            $code = $prefix . $branchCode . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        } while (Warehouse::where('warehouse_code', $code)->exists());

        return $code;
    }

    /**
     * Check if warehouse can be deleted.
     */
    public function canDeleteWarehouse(Warehouse $warehouse): bool
    {
        // Check if warehouse has inventory
        if ($warehouse->inventory()->exists()) {
            return false;
        }

        // Check if warehouse has locations
        if ($warehouse->locations()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get warehouse capacity utilization.
     */
    public function getCapacityUtilization(Warehouse $warehouse): array
    {
        $currentStock = $warehouse->inventory()->sum('quantity_on_hand');
        $maxCapacity = $warehouse->max_capacity_units ?? 0;

        $utilization = $maxCapacity > 0 ? ($currentStock / $maxCapacity) * 100 : 0;

        return [
            'current_stock' => $currentStock,
            'max_capacity' => $maxCapacity,
            'utilization_percentage' => round($utilization, 2),
            'status' => $this->getCapacityStatus($utilization),
        ];
    }

    /**
     * Get capacity status based on utilization percentage.
     */
    private function getCapacityStatus(float $utilization): string
    {
        if ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'high';
        } elseif ($utilization >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get warehouse statistics.
     */
    public function getWarehouseStats(?int $branchId = null): array
    {
        $query = Warehouse::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $warehouses = $query->with('inventory')->get();

        $stats = [
            'total_warehouses' => $warehouses->count(),
            'active_warehouses' => $warehouses->where('status', 'active')->count(),
            'inactive_warehouses' => $warehouses->where('status', 'inactive')->count(),
            'maintenance_warehouses' => $warehouses->where('status', 'maintenance')->count(),
            'total_capacity' => $warehouses->sum('max_capacity_units'),
            'total_current_stock' => $warehouses->sum(function ($warehouse) {
                return $warehouse->inventory->sum('quantity_on_hand');
            }),
            'average_utilization' => 0,
            'capacity_by_type' => [],
        ];

        if ($stats['total_capacity'] > 0) {
            $stats['average_utilization'] = round(($stats['total_current_stock'] / $stats['total_capacity']) * 100, 2);
        }

        // Group by type
        $stats['capacity_by_type'] = $warehouses->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_capacity' => $group->sum('max_capacity_units'),
                'current_stock' => $group->sum(function ($warehouse) {
                    return $warehouse->inventory->sum('quantity_on_hand');
                }),
            ];
        });

        return $stats;
    }

    /**
     * Validate warehouse data before creation/update.
     */
    public function validateWarehouseData(array $data, ?Warehouse $warehouse = null): array
    {
        $errors = [];

        // Check if usable area is not greater than total area
        if (isset($data['usable_area_sqm']) && isset($data['total_area_sqm'])) {
            if ($data['usable_area_sqm'] > $data['total_area_sqm']) {
                $errors[] = 'Usable area cannot be greater than total area.';
            }
        }

        // Check operating hours
        if (isset($data['opening_time']) && isset($data['closing_time'])) {
            $opening = strtotime($data['opening_time']);
            $closing = strtotime($data['closing_time']);

            if ($closing <= $opening) {
                $errors[] = 'Closing time must be after opening time.';
            }
        }

        // Check warehouse code uniqueness
        if (isset($data['warehouse_code'])) {
            $query = Warehouse::where('warehouse_code', $data['warehouse_code']);

            if ($warehouse) {
                $query->where('id', '!=', $warehouse->id);
            }

            if ($query->exists()) {
                $errors[] = 'Warehouse code must be unique.';
            }
        }

        return $errors;
    }
}
