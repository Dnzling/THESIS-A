<?php
// backend/app/Models/Inventory/CountSheet.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;

class CountSheet extends Model
{
    protected $fillable = [
        'stock_count_id',
        'branch_inventory_id',
        'product_id',
        'variation_id',
        'system_quantity',
        'system_unit_cost',
        'system_total_value',
        'counted_quantity',
        'counted_unit_cost',
        'counted_total_value',
        'discrepancy',
        'discrepancy_value',
        'warehouse_section',
        'aisle',
        'rack',
        'shelf',
        'bin_code',
        'counted_at',
        'counted_by',
        'count_status',
        'notes',
        'discrepancy_reason',
    ];

    protected $casts = [
        'system_unit_cost' => 'decimal:2',
        'system_total_value' => 'decimal:2',
        'counted_unit_cost' => 'decimal:2',
        'counted_total_value' => 'decimal:2',
        'discrepancy_value' => 'decimal:2',
        'counted_at' => 'datetime',
    ];

    // Relationships
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'counted_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('count_status', 'pending');
    }

    public function scopeCounted($query)
    {
        return $query->where('count_status', 'counted');
    }

    public function scopeWithDiscrepancy($query)
    {
        return $query->where('count_status', 'discrepancy_found');
    }

    public function scopeVerified($query)
    {
        return $query->where('count_status', 'verified');
    }

    // Helper methods
    public function hasDiscrepancy(): bool
    {
        return $this->discrepancy !== 0 && $this->discrepancy !== null;
    }

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }

    public function calculateDiscrepancy(): void
    {
        if ($this->counted_quantity !== null) {
            $this->discrepancy = $this->counted_quantity - $this->system_quantity;
            $this->discrepancy_value = $this->discrepancy * $this->system_unit_cost;
            
            if ($this->discrepancy !== 0) {
                $this->count_status = 'discrepancy_found';
            } else {
                $this->count_status = 'counted';
            }
        }
    }

    public function getDiscrepancyType(): string
    {
        if (!$this->hasDiscrepancy()) {
            return 'none';
        }

        return $this->discrepancy > 0 ? 'overage' : 'shortage';
    }

    public function getDiscrepancyPercentage(): float
    {
        if ($this->system_quantity == 0) {
            return $this->counted_quantity > 0 ? 100 : 0;
        }

        return round((abs($this->discrepancy) / $this->system_quantity) * 100, 2);
    }
}
