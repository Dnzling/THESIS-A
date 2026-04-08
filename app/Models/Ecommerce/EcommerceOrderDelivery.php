<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceOrderDelivery extends Model
{
    protected $table = 'ecommerce_order_deliveries';

    protected $fillable = [
        'order_id',
        'store_id',
        'trip_id',
        'vehicle_id',
        'driver_user_id',
        'tracking_number',
        'courier_name',
        'courier_contact',
        'status',
        'estimated_delivery_at',
        'distance_km',
        'per_km_charge',
        'estimated_fee',
        'dispatched_at',
        'out_for_delivery_at',
        'delivered_at',
        'failed_reason',
        'proof_of_delivery_path',
        'proof_signature_path',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_delivery_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'per_km_charge' => 'decimal:2',
        'estimated_fee' => 'decimal:2',
        'dispatched_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(EcommerceDeliveryVehicle::class, 'vehicle_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EcommerceDeliveryLog::class, 'delivery_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Logistics\DeliveryTrip::class, 'trip_id');
    }
}
