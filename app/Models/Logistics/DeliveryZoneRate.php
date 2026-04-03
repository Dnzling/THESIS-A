<?php

namespace App\Models\Logistics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZoneRate extends Model
{
    protected $table = 'logistics_delivery_zone_rates';

    protected $fillable = [
        'zone_id',
        'min_distance_km',
        'max_distance_km',
        'min_weight_kg',
        'max_weight_kg',
        'base_fee',
        'per_km_fee',
        'per_kg_fee',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'min_distance_km' => 'decimal:2',
        'max_distance_km' => 'decimal:2',
        'min_weight_kg' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'per_kg_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }
}

