<?php
// app/Models/ProductCatalog/Category.php

namespace App\Models\ProductCatalog;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';
    
    protected $fillable = [
        'store_id',
        'category_code',
        'category_name',
        'description',
        'parent_category_id',
        'level',
        'icon_path',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'display_order' => 'integer'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function subcategoryProducts()
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_category_id');
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Accessors
    public function getFullPathAttribute()
    {
        $path = [$this->category_name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->category_name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}