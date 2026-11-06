<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class SalesRefund extends Model
{
    // Compatibility alias while legacy Sales callers are migrated to FinanceRefund.
    protected $table = 'finance_refunds';

    protected $fillable = [
        'store_id',
        'branch_id',
        'order_type',
        'order_id',
        'order_number',
        'customer_name',
        'reason',
        'amount',
        'refund_method',
        'refund_account_name',
        'refund_account_number',
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
