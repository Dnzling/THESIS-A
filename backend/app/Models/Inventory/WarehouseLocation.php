<?php
// backend/app/Models/Inventory/WarehouseLocation.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class WarehouseLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'location_code',
        'name',
        'description',
        'type',
        'status',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'max_capacity_units',
        'current_stock_units',
        'max_weight_kg',
        'current_weight_kg',
        'dimensions',
        'is_temperature_controlled',
        'min_temperature_c',
        'max_temperature_c',
        'requires_special_handling',
        'special_handling_instructions',
        'last_inventory_check',
    ];

    protected $casts = [
        'max_capacity_units' => 'decimal:2',
        'current_stock_units' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'current_weight_kg' => 'decimal:2',
        'dimensions' => 'array',
        'is_temperature_controlled' => 'boolean',
        'min_temperature_c' => 'decimal:2',
        'max_temperature_c' => 'decimal:2',
        'requires_special_handling' => 'boolean',
        'last_inventory_check' => 'datetime',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(BranchInventory::class, 'warehouse_location', 'location_code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                    ->whereRaw('current_stock_units < max_capacity_units');
    }

    public function scopeTemperatureControlled($query)
    {
        return $query->where('is_temperature_controlled', true);
    }

    // Helper methods
    public function getFullLocationCode(): string
    {
        $parts = array_filter([$this->aisle, $this->rack, $this->shelf, $this->bin]);
        return implode('-', $parts) ?: $this->location_code;
    }

    public function getCapacityUtilization(): float
    {
        if (!$this->max_capacity_units || $this->max_capacity_units == 0) {
            return 0;
        }

        return round(($this->current_stock_units / $this->max_capacity_units) * 100, 2);
    }

    public function getWeightUtilization(): float
    {
        if (!$this->max_weight_kg || $this->max_weight_kg == 0) {
            return 0;
        }

        return round(($this->current_weight_kg / $this->max_weight_kg) * 100, 2);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' &&
               (!$this->max_capacity_units || $this->current_stock_units < $this->max_capacity_units);
    }

    public function canAccommodate(float $quantity, float $weight = 0): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // Check capacity
        if ($this->max_capacity_units && ($this->current_stock_units + $quantity) > $this->max_capacity_units) {
            return false;
        }

        // Check weight
        if ($this->max_weight_kg && ($this->current_weight_kg + $weight) > $this->max_weight_kg) {
            return false;
        }

        return true;
    }

    public function getTemperatureRange(): ?string
    {
        if (!$this->is_temperature_controlled) {
            return null;
        }

        if ($this->min_temperature_c && $this->max_temperature_c) {
            return "{$this->min_temperature_c}°C to {$this->max_temperature_c}°C";
        }

        return 'Temperature controlled';
    }

    public function needsInventoryCheck(): bool
    {
        if (!$this->last_inventory_check) {
            return true;
        }

        // Check if inventory check is older than 30 days
        return $this->last_inventory_check->diffInDays(Carbon::now()) > 30;
    }

    public function updateStockLevels(float $quantityChange, float $weightChange = 0): void
    {
        $this->current_stock_units += $quantityChange;
        $this->current_weight_kg += $weightChange;

        // Ensure we don't go below zero
        $this->current_stock_units = max(0, $this->current_stock_units);
        $this->current_weight_kg = max(0, $this->current_weight_kg);

        // Update status based on capacity
        if ($this->max_capacity_units && $this->current_stock_units >= $this->max_capacity_units) {
            $this->status = 'full';
        } elseif ($this->status === 'full' && $this->current_stock_units < $this->max_capacity_units) {
            $this->status = 'active';
        }

        $this->save();
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function getVolume(): ?float
    {
        if (!$this->dimensions) {
            return null;
        }

        $dims = $this->dimensions;
        if (isset($dims['width']) && isset($dims['height']) && isset($dims['depth'])) {
            return $dims['width'] * $dims['height'] * $dims['depth'];
        }

        return null;
    }
}
