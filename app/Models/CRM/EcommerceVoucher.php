<?php

namespace App\Models\CRM;

use App\Models\Store\Store;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceVoucher extends Model
{
    protected $table = 'ecommerce_vouchers';

    protected $fillable = [
        'store_id',
        'created_by',
        'voucher_name',
        'voucher_slots',
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
