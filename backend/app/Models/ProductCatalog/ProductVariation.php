<?php
// app/Models/ProductCatalog/ProductVariation.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $table = 'product_variations';
    
    protected $fillable = [
        'store_id',
        'product_id',
        'variation_sku',
        'variation_name',
        'color',
        'color_hex',
        'size',
        'material',
        'price_adjustment',
        'custom_3d_model_id',
        'is_active'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean'
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

    public function custom3dModel()
    {
        return $this->belongsTo(ProductAsset::class, 'custom_3d_model_id');
    }

    public function pricingHistory()
    {
        return $this->hasMany(PricingHistory::class, 'variation_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->product->current_price + $this->price_adjustment;
    }

    public function getDisplayNameAttribute()
    {
        $parts = [];
        
        if ($this->color) {
            $parts[] = $this->color;
        }
        
        if ($this->size) {
            $parts[] = $this->size;
        }
        
        if ($this->material) {
            $parts[] = $this->material;
        }
        
        return $this->product->product_name . ' - ' . implode(' / ', $parts);
    }
}