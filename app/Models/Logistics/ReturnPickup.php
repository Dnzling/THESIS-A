<?php

namespace App\Models\Logistics;

use App\Models\Core\User;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Branch;

class ReturnPickup extends Model
{
    protected $table = 'logistics_return_pickups';

    protected $fillable = [
        'store_id',
        'return_id',
        'status',
        'scheduled_at',
        'pickup_name',
        'pickup_phone',
        'pickup_address',
        'driver_user_id',
        'vehicle_id',
        'destination_branch_id',
        'assistant_user_ids',
        'distance_km',
        'estimated_fee',
        'notes',
        'proof_photo_path',
        'proof_signature_path',
        'picked_up_at',
        'current_latitude', 'current_longitude', 'current_address',
        'out_for_delivery_at', 'delivered_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'assistant_user_ids' => 'array',
        'distance_km' => 'decimal:2',
        'estimated_fee' => 'decimal:2',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderReturn::class, 'return_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(EcommerceDeliveryVehicle::class, 'vehicle_id');
    }

    public function destinationBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'destination_branch_id'); }
    public function logs(): HasMany { return $this->hasMany(ReturnPickupLog::class, 'return_pickup_id')->latest(); }
}
