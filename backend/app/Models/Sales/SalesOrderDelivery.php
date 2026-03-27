<?php

namespace App\Models\Sales;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderDelivery extends Model
{
    protected $table = 'order_deliveries';

    protected $fillable = [
        'sales_order_id',
        'store_id',
        'branch_id',
        'driver_user_id',
        'tracking_number',
        'courier_name',
        'courier_contact',
        'status',
        'scheduled_delivery_at',
        'distance_km',
        'per_km_charge',
        'estimated_fee',
        'dispatched_at',
        'out_for_delivery_at',
        'delivered_at',
        'failed_reason',
        'notes',
        'proof_of_delivery_path',
        'proof_signature_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_delivery_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'per_km_charge' => 'decimal:2',
        'estimated_fee' => 'decimal:2',
        'dispatched_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SalesOrderDeliveryLog::class, 'delivery_id');
    }
}
