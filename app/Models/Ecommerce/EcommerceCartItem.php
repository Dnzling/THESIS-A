<?php

namespace App\Models\Ecommerce;

use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceCartItem extends Model
{
    protected $table = 'ecommerce_cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'variation_id',
        'variation_name',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'variation_id' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(EcommerceCart::class, 'cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}
