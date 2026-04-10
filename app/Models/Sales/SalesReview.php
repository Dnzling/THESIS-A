<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\ProductCatalog\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReview extends Model
{
    protected $table = 'sales_reviews';

    protected $fillable = [
        'store_id',
        'branch_id',
        'order_type',
        'order_id',
        'product_id',
        'customer_name',
        'customer_contact',
        'rating',
        'message',
        'reply',
        'replied_by',
        'replied_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'replied_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
