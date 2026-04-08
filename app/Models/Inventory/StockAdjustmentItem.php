<?php
// backend/app/Models/Inventory/StockAdjustmentItem.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'variation_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'unit_cost',
        'value_difference',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'unit_cost' => 'decimal:2',
        'value_difference' => 'decimal:2',
    ];

    // Relationships
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }
}