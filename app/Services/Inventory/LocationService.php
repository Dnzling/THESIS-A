<?php
// backend/app/Services/Inventory/LocationService.php

namespace App\Services\Inventory;

use App\Models\Inventory\WarehouseLocation;
use Illuminate\Support\Facades\Log;
use Exception;

class LocationService
{
    /**
     * Create a new warehouse location.
     */
    public function createLocation(array $data): WarehouseLocation
    {
        try {
            // Generate location code if not provided
            if (!isset($data['location_code']) || empty($data['location_code'])) {
                $data['location_code'] = $this->generateLocationCode($data['warehouse_id'], $data['type']);
            }

            // Set default values
            $data['current_stock_units'] = $data['current_stock_units'] ?? 0;
            $data['current_weight_kg'] = $data['current_weight_kg'] ?? 0;

            $location = WarehouseLocation::create($data);

            Log::info('Warehouse location created successfully', [
                'location_id' => $location->id,
                'location_code' => $location->location_code,
                'warehouse_id' => $location->warehouse_id,
                'name' => $location->name,
            ]);

            return $location;

        } catch (Exception $e) {
            Log::error('Failed to create warehouse location', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing warehouse location.
     */
    public function updateLocation(WarehouseLocation $location, array $data): WarehouseLocation
    {
        try {
            $oldData = $location->toArray();

            $location->update($data);

            Log::info('Warehouse location updated successfully', [
                'location_id' => $location->id,
                'location_code' => $location->location_code,
                'changes' => array_diff_assoc($data, $oldData),
            ]);

            return $location;

        } catch (Exception $e) {
            Log::error('Failed to update warehouse location', [
                'location_id' => $location->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique location code.
     */
    private function generateLocationCode(int $warehouseId, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 2)); // First 2 letters of type
        $warehouseCode = str_pad($warehouseId, 3, '0', STR_PAD_LEFT);

        $counter = 1;
        do {
            $code = $prefix . $warehouseCode . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        } while (WarehouseLocation::where('location_code', $code)->exists());

        return $code;
    }

    /**
     * Update stock levels for a location.
     */
    public function updateStockLevels(WarehouseLocation $location, float $quantityChange, float $weightChange = 0): void
    {
        try {
            $location->updateStockLevels($quantityChange, $weightChange);

            Log::info('Location stock levels updated', [
                'location_id' => $location->id,
                'location_code' => $location->location_code,
                'quantity_change' => $quantityChange,
                'weight_change' => $weightChange,
                'new_stock' => $location->current_stock_units,
                'new_weight' => $location->current_weight_kg,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update location stock levels', [
                'location_id' => $location->id,
                'quantity_change' => $quantityChange,
                'weight_change' => $weightChange,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if location can be deleted.
     */
    public function canDeleteLocation(WarehouseLocation $location): bool
    {
        // Check if location has inventory
        if ($location->inventory()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get available locations for a warehouse.
     */
    public function getAvailableLocations(int $warehouseId, array $filters = []): array
    {
        $query = WarehouseLocation::where('warehouse_id', $warehouseId)->available();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['temperature_controlled']) && $filters['temperature_controlled']) {
            $query->temperatureControlled();
        }

        $locations = $query->get();

        // Filter by capacity and weight if provided
        if (isset($filters['quantity']) || isset($filters['weight'])) {
            $quantity = $filters['quantity'] ?? 0;
            $weight = $filters['weight'] ?? 0;

            $locations = $locations->filter(function ($location) use ($quantity, $weight) {
                return $location->canAccommodate($quantity, $weight);
            });
        }

        return $locations->toArray();
    }

    /**
     * Get locations needing inventory check.
     */
    public function getLocationsNeedingCheck(int $warehouseId = null): array
    {
        $query = WarehouseLocation::active();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()
            ->filter(fn($location) => $location->needsInventoryCheck())
            ->values()
            ->toArray();
    }

    /**
     * Bulk update inventory check timestamps.
     */
    public function bulkUpdateInventoryCheck(array $locationIds): int
    {
        try {
            $count = WarehouseLocation::whereIn('id', $locationIds)
                ->update(['last_inventory_check' => now()]);

            Log::info('Bulk inventory check update completed', [
                'location_ids' => $locationIds,
                'updated_count' => $count,
            ]);

            return $count;

        } catch (Exception $e) {
            Log::error('Failed to bulk update inventory checks', [
                'location_ids' => $locationIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get location utilization statistics.
     */
    public function getLocationStats(int $warehouseId = null): array
    {
        $query = WarehouseLocation::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $locations = $query->get();

        $stats = [
            'total_locations' => $locations->count(),
            'active_locations' => $locations->where('status', 'active')->count(),
            'inactive_locations' => $locations->where('status', 'inactive')->count(),
            'maintenance_locations' => $locations->where('status', 'maintenance')->count(),
            'full_locations' => $locations->where('status', 'full')->count(),
            'temperature_controlled' => $locations->where('is_temperature_controlled', true)->count(),
            'average_capacity_utilization' => 0,
            'average_weight_utilization' => 0,
            'by_type' => [],
        ];

        // Calculate utilization averages
        $capacityUtilizations = $locations->map(fn($loc) => $loc->getCapacityUtilization())->filter();
        $weightUtilizations = $locations->map(fn($loc) => $loc->getWeightUtilization())->filter();

        if ($capacityUtilizations->count() > 0) {
            $stats['average_capacity_utilization'] = round($capacityUtilizations->avg(), 2);
        }

        if ($weightUtilizations->count() > 0) {
            $stats['average_weight_utilization'] = round($weightUtilizations->avg(), 2);
        }

        // Group by type
        $stats['by_type'] = $locations->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'active' => $group->where('status', 'active')->count(),
                'average_utilization' => round($group->map(fn($loc) => $loc->getCapacityUtilization())->avg(), 2),
            ];
        });

        return $stats;
    }

    /**
     * Validate location data before creation/update.
     */
    public function validateLocationData(array $data, ?WarehouseLocation $location = null): array
    {
        $errors = [];

        // Check if current stock exceeds max capacity
        if (isset($data['current_stock_units']) && isset($data['max_capacity_units'])) {
            if ($data['current_stock_units'] > $data['max_capacity_units']) {
                $errors[] = 'Current stock cannot exceed maximum capacity.';
            }
        }

        // Check if current weight exceeds max weight
        if (isset($data['current_weight_kg']) && isset($data['max_weight_kg'])) {
            if ($data['current_weight_kg'] > $data['max_weight_kg']) {
                $errors[] = 'Current weight cannot exceed maximum weight.';
            }
        }

        // Check temperature range
        if (isset($data['is_temperature_controlled']) && $data['is_temperature_controlled']) {
            if (!isset($data['min_temperature_c']) || !isset($data['max_temperature_c'])) {
                $errors[] = 'Temperature range is required for temperature controlled locations.';
            } elseif ($data['max_temperature_c'] < $data['min_temperature_c']) {
                $errors[] = 'Maximum temperature must be greater than minimum temperature.';
            }
        }

        // Check special handling instructions
        if (isset($data['requires_special_handling']) && $data['requires_special_handling']) {
            if (!isset($data['special_handling_instructions']) || empty($data['special_handling_instructions'])) {
                $errors[] = 'Special handling instructions are required when special handling is required.';
            }
        }

        // Check location code uniqueness
        if (isset($data['location_code'])) {
            $query = WarehouseLocation::where('location_code', $data['location_code']);

            if ($location) {
                $query->where('id', '!=', $location->id);
            }

            if ($query->exists()) {
                $errors[] = 'Location code must be unique.';
            }
        }

        return $errors;
    }
}