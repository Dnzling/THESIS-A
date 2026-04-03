<?php
// app/Models/ProductCatalog/RelatedProduct.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;

class RelatedProduct extends Model
{
    protected $table = 'related_products';
    
    protected $fillable = [
        'store_id',
        'product_id',
        'related_product_id',
        'relation_type',
        'strength_score'
    ];

    protected $casts = [
        'strength_score' => 'decimal:2'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function relatedProduct()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    // Scopes
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('relation_type', $type);
    }

    public function scopeStrongest($query)
    {
        return $query->orderBy('strength_score', 'desc');
    }
}