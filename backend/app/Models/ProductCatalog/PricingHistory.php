<?php
// app/Models/ProductCatalog/PricingHistory.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;

class PricingHistory extends Model
{
    protected $table = 'pricing_history';
    
    protected $fillable = [
        'store_id',
        'product_id',
        'variation_id',
        'old_price',
        'new_price',
        'price_type',
        'reason',
        'effective_date',
        'created_by'
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'effective_date' => 'datetime'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    // Scopes
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('effective_date', [$start, $end]);
    }

    // Accessors
    public function getPriceChangeAttribute()
    {
        return $this->new_price - $this->old_price;
    }

    public function getPriceChangePercentageAttribute()
    {
        if ($this->old_price > 0) {
            return round(($this->price_change / $this->old_price) * 100, 2);
        }
        
        return 0;
    }
}