<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class SalesRefund extends Model
{
    protected $table = 'sales_refunds';

    protected $fillable = [
        'store_id',
        'branch_id',
        'order_type',
        'order_id',
        'order_number',
        'customer_name',
        'reason',
        'amount',
        'status',
        'requested_by',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];
}
