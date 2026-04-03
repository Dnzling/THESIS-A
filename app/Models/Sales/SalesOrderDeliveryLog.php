<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderDeliveryLog extends Model
{
    protected $table = 'order_delivery_logs';

    protected $fillable = [
        'delivery_id',
        'sales_order_id',
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
        return $this->belongsTo(SalesOrderDelivery::class, 'delivery_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
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
