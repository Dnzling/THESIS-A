<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

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
}
