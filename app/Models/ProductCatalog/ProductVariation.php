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
        'texture',
        'finish',
        'price_adjustment',
        'base_price',
        'discounted_price',
        'cost_price',
        'reorder_point',
        'supplier_name',
        'unit_of_measurement',
        'is_baseline',
        'custom_3d_model_id',
        'custom_image_id',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_kg',
        'is_active'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'base_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'reorder_point' => 'integer',
        'is_baseline' => 'boolean',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
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

    public function customImage()
    {
        return $this->belongsTo(ProductAsset::class, 'custom_image_id');
    }

    public function pricingHistory()
    {
        return $this->hasMany(PricingHistory::class, 'variation_id');
    }

    public function inventory()
    {
        return $this->hasMany(\App\Models\Inventory\BranchInventory::class, 'variation_id');
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
        return $this->discounted_price
            ?? $this->base_price
            ?? ($this->product->current_price + $this->price_adjustment);
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
