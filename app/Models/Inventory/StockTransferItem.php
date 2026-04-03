<?php
// backend/app/Models/Inventory/StockTransferItem.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class StockTransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'variation_id',
        'requested_quantity',
        'approved_quantity',
        'shipped_quantity',
        'received_quantity',
        'damaged_quantity',
        'unit_value',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'received_quantity' => 'integer',
        'damaged_quantity' => 'integer',
        'unit_value' => 'decimal:2',
    ];

    // Relationships
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
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