<?php
// backend/app/Models/Procurement/RFQ/SupplierQuotationItem.php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'rfq_item_id',
        'unit_price',
        'quantity',
        'discount_percent',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'discount_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'quotation_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }

    // Accessors
    public function getDiscountedPriceAttribute(): float
    {
        return $this->unit_price * (1 - ($this->discount_percent / 100));
    }

    public function getTotalAfterDiscountAttribute(): float
    {
        return $this->discounted_price * $this->quantity;
    }

    // Helper Methods
    public function isLowestPrice(): bool
    {
        $lowestPrice = $this->rfqItem->quotationItems()
            ->whereHas('quotation', function ($query) {
                $query->where('status', 'submitted');
            })
            ->min('unit_price');

        return $this->unit_price <= $lowestPrice;
    }

    public function getPriceRankAttribute(): int
    {
        return $this->rfqItem->quotationItems()
            ->whereHas('quotation', function ($query) {
                $query->where('status', 'submitted');
            })
            ->where('unit_price', '<', $this->unit_price)
            ->count() + 1;
    }
}