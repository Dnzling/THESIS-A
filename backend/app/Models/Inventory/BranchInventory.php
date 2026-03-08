<?php
// backend/app/Models/Inventory/BranchInventory.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;

class BranchInventory extends Model
{
    use SoftDeletes;

    protected $table = 'branch_inventory';

    protected $fillable = [
        'store_id',
        'branch_id',
        'product_id',
        'variation_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'quantity_damaged',
        'quantity_incoming',
        'warehouse_section',
        'aisle',
        'rack',
        'shelf',
        'bin_code',
        'reorder_point',
        'reorder_quantity',
        'maximum_stock',
        'safety_stock',
        'stock_status',
        'unit_cost',
        'average_cost',
        'total_value',
        'last_stock_count_date',
        'last_counted_quantity',
        'last_counted_by',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_available' => 'integer',
        'quantity_damaged' => 'integer',
        'quantity_incoming' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'maximum_stock' => 'integer',
        'safety_stock' => 'integer',
        'unit_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'last_stock_count_date' => 'date',
        'last_counted_quantity' => 'integer',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function lastCountedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'last_counted_by');
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->where('stock_status', 'low_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_status', 'out_of_stock');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    // Helper Methods
    public function isLowStock(): bool
    {
        return $this->quantity_available <= $this->reorder_point;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_available <= 0;
    }

    public function updateStockStatus(): void
    {
        if ($this->quantity_available <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->quantity_available <= $this->reorder_point) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }
        $this->save();
    }

    public function calculateTotalValue(): void
    {
        $this->total_value = $this->quantity_on_hand * $this->average_cost;
        $this->save();
    }

    public function getFullLocationAttribute(): string
    {
        return $this->bin_code ?? "{$this->warehouse_section}-{$this->aisle}-{$this->rack}-{$this->shelf}";
    }
}