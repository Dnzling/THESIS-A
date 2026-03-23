<?php

namespace App\Models\Store;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDeliveryFeeSetting extends Model
{
    protected $table = 'store_delivery_fee_settings';

    protected $fillable = [
        'store_id',
        'is_active',
        'base_fee',
        'per_km_fee',
        'min_delivery_fee',
        'free_shipping_min_order',
        'bulky_item_surcharge',
        'remote_area_surcharge',
        'max_delivery_distance_km',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_fee' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'min_delivery_fee' => 'decimal:2',
        'free_shipping_min_order' => 'decimal:2',
        'bulky_item_surcharge' => 'decimal:2',
        'remote_area_surcharge' => 'decimal:2',
        'max_delivery_distance_km' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

