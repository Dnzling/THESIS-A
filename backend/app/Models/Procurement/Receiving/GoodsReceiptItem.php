<?php
// backend/app/Models/Procurement/Receiving/GoodsReceiptItem.php

namespace App\Models\Procurement\Receiving;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\PurchaseOrder\PurchaseOrderItem;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'variation_id',
        'quantity_expected',
        'quantity_received',
        'quantity_damaged',
        'condition',
        'notes',
    ];

    protected $casts = [
        'quantity_expected' => 'integer',
        'quantity_received' => 'integer',
        'quantity_damaged' => 'integer',
    ];

    // Relationships
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    // Accessors
    public function getQuantityDiscrepancyAttribute(): int
    {
        return $this->quantity_expected - $this->quantity_received - $this->quantity_damaged;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->quantity_discrepancy != 0 || $this->quantity_damaged > 0;
    }

    public function getReceivalPercentageAttribute(): float
    {
        if ($this->quantity_expected === 0) {
            return 0;
        }

        return round(($this->quantity_received / $this->quantity_expected) * 100, 2);
    }
}