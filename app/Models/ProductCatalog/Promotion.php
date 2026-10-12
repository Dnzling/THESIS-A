<?php

namespace App\Models\ProductCatalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id', 'name', 'description', 'discount_type', 'discount_value',
        'target_type', 'minimum_order_amount', 'starts_at', 'ends_at',
        'priority', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_categories');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }
}
