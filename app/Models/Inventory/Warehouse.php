<?php
// backend/app/Models/Inventory/Warehouse.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_code',
        'name',
        'description',
        'type',
        'status',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'manager_name',
        'manager_phone',
        'total_area_sqm',
        'usable_area_sqm',
        'total_racks',
        'total_shelves',
        'max_capacity_units',
        'opening_time',
        'closing_time',
        'operating_days',
        'requires_access_card',
        'has_security_system',
        'has_fire_system',
        'access_instructions',
    ];

    protected $casts = [
        'total_area_sqm' => 'decimal:2',
        'usable_area_sqm' => 'decimal:2',
        'operating_days' => 'array',
        'requires_access_card' => 'boolean',
        'has_security_system' => 'boolean',
        'has_fire_system' => 'boolean',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(BranchInventory::class, 'warehouse_section', 'warehouse_code');
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

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Helper methods
    public function getFullAddress(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        $address .= ', ' . $this->city;
        
        if ($this->state) {
            $address .= ', ' . $this->state;
        }
        
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        
        $address .= ', ' . $this->country;
        
        return $address;
    }

    public function getCapacityUtilization(): float
    {
        if (!$this->max_capacity_units) {
            return 0;
        }

        $currentStock = $this->inventory()->sum('quantity_on_hand');
        return round(($currentStock / $this->max_capacity_units) * 100, 2);
    }

    public function isOperational(): bool
    {
        return $this->status === 'active';
    }

    public function getOperatingHours(): string
    {
        if (!$this->opening_time || !$this->closing_time) {
            return 'Not specified';
        }

        return $this->opening_time->format('H:i') . ' - ' . $this->closing_time->format('H:i');
    }

    public function getOperatingDays(): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $operatingDays = $this->operating_days ?? [];

        return array_map(function ($dayIndex) use ($days) {
            return $days[$dayIndex] ?? 'Unknown';
        }, $operatingDays);
    }
}
