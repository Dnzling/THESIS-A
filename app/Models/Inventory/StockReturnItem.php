<?php
// backend/app/Models/Inventory/StockReturnItem.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class StockReturnItem extends Model
{
    protected $fillable = [
        'stock_return_id',
        'product_id',
        'variation_id',
        'branch_inventory_id',
        'quantity_returned',
        'unit_cost',
        'total_cost',
        'unit_value',
        'total_value',
        'condition',
        'return_reason',
        'notes',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'unit_value' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    // Relationships
    public function stockReturn(): BelongsTo
    {
        return $this->belongsTo(StockReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class);
    }
}
