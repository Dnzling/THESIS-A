<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceDeliveryLog extends Model
{
    protected $table = 'ecommerce_delivery_logs';

    protected $fillable = [
        'delivery_id',
        'order_id',
        'store_id',
        'event_type',
        'status_from',
        'status_to',
        'message',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderDelivery::class, 'delivery_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

