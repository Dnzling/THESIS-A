<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceProductReview extends Model
{
    protected $table = 'ecommerce_product_reviews';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'store_id',
        'user_id',
        'rating',
        'review_text',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderItem::class, 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
