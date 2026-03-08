<?php
// app/Models/ProductCatalog/Product.php

namespace App\Models\ProductCatalog;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';
    
    protected $fillable = [
        'store_id',
        'sku',
        'product_name',
        'description',
        'category_id',
        'subcategory_id',
        'brand',
        'collection_name',
        'base_price',
        'cost_price',
        'discounted_price',
        'tax_rate',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_kg',
        'assembly_required',
        'is_featured',
        'is_new_arrival',
        'is_bestseller',
        'is_active',
        'stock_status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'tags',
        'published_at'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'assembly_required' => 'boolean',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime'
    ];

    protected $hidden = [
        'cost_price',
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function assets()
    {
        return $this->hasMany(ProductAsset::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function pricingHistory()
    {
        return $this->hasMany(PricingHistory::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags')
                    ->withTimestamps();
    }

    public function relatedProducts()
    {
        return $this->hasMany(RelatedProduct::class, 'product_id');
    }

    public function relatedFrom()
    {
        return $this->hasMany(RelatedProduct::class, 'related_product_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereIn('stock_status', ['In Stock', 'Low Stock']);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNewArrivals($query)
    {
        return $query->where('is_new_arrival', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId)
                     ->orWhere('subcategory_id', $categoryId);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('base_price', [$min, $max]);
    }

    // Accessors
    public function getCurrentPriceAttribute()
    {
        return $this->discounted_price ?? $this->base_price;
    }

    /**
     * Protect cost price visibility unless user has explicit permission.
     */
    public function getCostPriceAttribute($value)
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if ($user instanceof User && $user->hasPermissionTo('finance.products.view.store', $this->store_id)) {
            return $value;
        }

        return null;
    }

    public function getDisplayPriceAttribute()
    {
        return $this->discounted_price ?? $this->base_price;
    }

    public function getProfitMarginAttribute()
    {
        $costPrice = (float) $this->getRawOriginal('cost_price');
        $displayPrice = (float) ($this->discounted_price ?? $this->base_price ?? 0);

        if ($costPrice <= 0 || $displayPrice <= 0) {
            return null;
        }

        return round((($displayPrice - $costPrice) / $displayPrice) * 100, 2);
    }

    public function getDimensionsAttribute()
    {
        return "{$this->length_cm} x {$this->width_cm} x {$this->height_cm} cm";
    }

    public function getPrimary3dModelAttribute()
    {
        return $this->assets()
                    ->where('asset_type', '3D_Model')
                    ->where('is_primary', true)
                    ->first();
    }

    public function getAll3dAssetsAttribute()
    {
        return $this->assets()
                    ->whereIn('asset_type', ['3D_Model', '3D_Thumbnail'])
                    ->get();
    }
}