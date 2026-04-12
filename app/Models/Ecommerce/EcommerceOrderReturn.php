<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Logistics\ReturnPickup;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EcommerceOrderReturn extends Model
{
    protected $table = 'ecommerce_order_returns';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'store_id',
        'user_id',
        'requested_quantity',
        'reason',
        'details',
        'evidence_urls',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'reviewed_at' => 'datetime',
        'evidence_urls' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderItem::class, 'order_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(ReturnPickup::class, 'return_id');
    }
}
