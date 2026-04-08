<?php
// app/Models/ProductCatalog/ProductAttribute.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use SoftDeletes;

    protected $table = 'product_attributes';
    
    protected $fillable = [
        'store_id',
        'attribute_name',
        'attribute_type',
        'is_filterable',
        'display_order'
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'display_order' => 'integer'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    // Scopes
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}