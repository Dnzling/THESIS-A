<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EcommerceOrder extends Model
{
    protected $table = 'ecommerce_orders';

    protected $fillable = [
        'store_id',
        'assigned_branch_id',
        'user_id',
        'pending_cart_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'shipping_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address',
        'customer_latitude',
        'customer_longitude',
        'subtotal',
        'tax_amount',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'notes',
        'pending_snapshot',
        'placed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'customer_latitude' => 'decimal:7',
        'customer_longitude' => 'decimal:7',
        'pending_snapshot' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class, 'order_id');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(EcommerceOrderCancellation::class, 'order_id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(EcommerceOrderReturn::class, 'order_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(EcommerceOrderDelivery::class, 'order_id');
    }
}
