<?php

namespace App\Models\ProductCatalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Core\User;

class Unit extends Model
{
    protected $fillable = [
        'store_id',
        'unit_name',
        'unit_code',
        'unit_symbol',
        'description',
        'unit_type',
        'conversion_factor',
        'base_unit_id',
        'is_base_unit',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the store that owns the unit
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the base unit for conversions
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get units that use this as base unit
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * Get the user who created the unit
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the unit
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get products that use this unit
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    /**
     * Scope to get only active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get base units
     */
    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    /**
     * Convert quantity from this unit to base unit
     */
    public function toBaseUnit(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit to this unit
     */
    public function fromBaseUnit(float $quantity): float
    {
        return $quantity / $this->conversion_factor;
    }

    /**
     * Convert quantity between units
     */
    public static function convert(float $quantity, Unit $fromUnit, Unit $toUnit): float
    {
        // Convert to base unit first, then to target unit
        $baseQuantity = $fromUnit->toBaseUnit($quantity);
        return $toUnit->fromBaseUnit($baseQuantity);
    }
}
