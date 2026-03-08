<?php
// backend/app/Models/Procurement/RFQ/RFQItem.php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class RFQItem extends Model
{
    protected $table = 'rfq_items';

    protected $fillable = [
        'rfq_id',
        'product_id',
        'variation_id',
        'quantity',
        'specifications',
        'requirements',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class, 'rfq_item_id');
    }

    // Helper Methods
    public function getLowestQuoteAttribute(): ?SupplierQuotationItem
    {
        return $this->quotationItems()
            ->whereHas('quotation', function ($query) {
                $query->where('status', 'submitted');
            })
            ->orderBy('unit_price', 'asc')
            ->first();
    }

    public function getQuotesCountAttribute(): int
    {
        return $this->quotationItems()
            ->whereHas('quotation', function ($query) {
                $query->where('status', 'submitted');
            })
            ->count();
    }
}