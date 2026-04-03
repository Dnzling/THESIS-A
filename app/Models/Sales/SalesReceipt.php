<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReceipt extends Model
{
    protected $table = 'sales_receipts';

    protected $fillable = [
        'store_id',
        'branch_id',
        'sales_order_id',
        'sales_payment_id',
        'receipt_number',
        'amount',
        'currency',
        'payment_method',
        'payment_reference',
        'issued_at',
        'issued_by',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'payload' => 'array',
    ];

    public function store(): BelongsTo { return $this->belongsTo(Store::class, 'store_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function order(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(SalesPayment::class, 'sales_payment_id'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
}

