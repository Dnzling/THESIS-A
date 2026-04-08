<?php
// backend/app/Models/Procurement/Supplier/SupplierPrice.php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;

class SupplierPrice extends Model
{
    protected $fillable = [
        'supplier_id',
        'product_id',
        'unit_price',
        'currency',
        'minimum_order_quantity',
        'lead_time_days',
        'pack_size',
        'effective_date',
        'expiry_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'minimum_order_quantity' => 'integer',
        'lead_time_days' => 'integer',
        'pack_size' => 'integer',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $dates = ['effective_date', 'expiry_date'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get active prices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });
    }

    /**
     * Scope to get prices for a specific product
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get price history for a product from a supplier
     */
    public function scopePriceHistory($query, int $supplierId, int $productId)
    {
        return $query->where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->orderBy('effective_date', 'desc');
    }

    /**
     * Calculate price variance from average
     */
    public function getPriceVarianceAttribute(): float
    {
        $avgPrice = SupplierPrice::byProduct($this->product_id)
            ->active()
            ->avg('unit_price');

        if ($avgPrice == 0) {
            return 0;
        }

        return round((($this->unit_price - $avgPrice) / $avgPrice) * 100, 2);
    }

    /**
     * Check if price is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }
}
