<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceDeliveryVehicle extends Model
{
    protected $table = 'ecommerce_delivery_vehicles';

    protected $fillable = [
        'store_id',
        'branch_id',
        'vehicle_name',
        'vehicle_type',
        'plate_number',
        'brand',
        'model',
        'color',
        'capacity_kg',
        'max_orders_per_trip',
        'status',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'capacity_kg' => 'decimal:2',
        'max_orders_per_trip' => 'integer',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(EcommerceOrderDelivery::class, 'vehicle_id');
    }
}
