<?php
// backend/app/Models/Procurement/PurchaseOrder/PurchaseOrderItem.php

namespace App\Models\Procurement\PurchaseOrder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Procurement\Requisition\PurchaseRequisitionItem;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_requisition_item_id',
        'product_id',
        'variation_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_rejected',
        'allocated_quantity',
        'unit_cost',
        'tax_rate',
        'discount_percent',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'purchase_requisition_item_id' => 'integer',
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_rejected' => 'integer',
        'allocated_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class, 'purchase_requisition_item_id');
    }

    // Accessors
    public function getQuantityPendingAttribute(): int
    {
        return $this->quantity_ordered - $this->quantity_received - $this->quantity_rejected;
    }

    public function getReceivalPercentageAttribute(): float
    {
        if ($this->quantity_ordered === 0) {
            return 0;
        }

        return round(($this->quantity_received / $this->quantity_ordered) * 100, 2);
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received + $this->quantity_rejected >= $this->quantity_ordered;
    }

    public function updateReceived(int $received, int $rejected = 0): void
    {
        $this->increment('quantity_received', $received);
        $this->increment('quantity_rejected', $rejected);
    }
}
