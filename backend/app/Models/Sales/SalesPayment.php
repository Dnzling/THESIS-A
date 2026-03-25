<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesPayment extends Model
{
    protected $table = 'sales_payments';

    protected $fillable = [
        'store_id',
        'branch_id',
        'sales_order_id',
        'crm_lead_id',
        'payment_provider',
        'payment_method',
        'currency',
        'amount',
        'status',
        'provider_reference',
        'checkout_url',
        'metadata',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function store(): BelongsTo { return $this->belongsTo(Store::class, 'store_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function order(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function receipt(): HasOne { return $this->hasOne(SalesReceipt::class, 'sales_payment_id'); }
}

