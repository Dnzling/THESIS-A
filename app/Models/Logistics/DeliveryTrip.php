<?php

namespace App\Models\Logistics;

use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use App\Models\Ecommerce\EcommerceOrderDelivery;
use App\Models\Sales\SalesOrderDelivery;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryTrip extends Model
{
    protected $table = 'logistics_delivery_trips';

    protected $fillable = [
        'store_id',
        'vehicle_id',
        'driver_user_id',
        'status',
        'scheduled_departure_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_departure_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(EcommerceDeliveryVehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function ecommerceDeliveries(): HasMany
    {
        return $this->hasMany(EcommerceOrderDelivery::class, 'trip_id');
    }

    public function salesDeliveries(): HasMany
    {
        return $this->hasMany(SalesOrderDelivery::class, 'trip_id');
    }
}

