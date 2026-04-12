<?php

namespace App\Models\Logistics;

use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceOrderReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'notes',
        'proof_photo_path',
        'proof_signature_path',
        'picked_up_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderReturn::class, 'return_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}

