<?php
// app/Models/ProductCatalog/Product.php

namespace App\Models\ProductCatalog;

use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceOrderItem;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Procurement\Supplier\Supplier;

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
        'unit_id',
        'product_type',
        'brand',
        'collection_name',
        'base_price',
        'cost_price',
        'discounted_price',
        'price_approval_status',
        'pending_base_price',
        'pending_discounted_price',
        'price_proposed_by',
        'price_proposed_at',
        'price_approved_by',
        'price_approved_at',
        'price_rejected_by',
        'price_rejected_at',
        'price_approval_notes',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_kg',
        'assembly_required',
        'is_featured',
        'is_new_arrival',
        'is_bestseller',
        'is_active',
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
        'pending_base_price' => 'decimal:2',
        'pending_discounted_price' => 'decimal:2',
        'price_proposed_at' => 'datetime',
        'price_approved_at' => 'datetime',
        'price_rejected_at' => 'datetime',
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

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
    public function inventory()
    {
        return $this->hasMany(\App\Models\Inventory\BranchInventory::class, 'product_id', 'id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_products')
            ->withPivot('supplier_sku', 'supplier_price', 'minimum_order_quantity', 'lead_time_days', 'is_preferred_supplier')
            ->withTimestamps();
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

    public function reconstructions()
    {
        return $this->hasMany(Product3DReconstruction::class, 'product_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function items()
    {
        return $this->hasMany(EcommerceOrderItem::class, 'product_id');
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
