<?php
// backend/app/Models/Procurement/Requisition/PurchaseRequisitionItem.php

namespace App\Models\Procurement\Requisition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id',
        'product_id',
        'variation_id',
        'quantity_requested',
        'estimated_unit_cost',
        'specifications',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'estimated_unit_cost' => 'decimal:2',
    ];

    // Relationships
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
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
    public function getEstimatedTotalAttribute(): float
    {
        return $this->quantity_requested * ($this->estimated_unit_cost ?? 0);
    }
}