<?php

namespace App\Models\Procurement\Shipping;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderDeliveryLog extends Model
{
    protected $fillable = [
        'shipment_id',
        'created_by',
        'event_type',
        'notes',
        'latitude',
        'longitude',
        'logged_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'logged_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderShipment::class, 'shipment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
