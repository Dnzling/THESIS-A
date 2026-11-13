<?php

namespace App\Models\Logistics;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnPickupLog extends Model
{
    protected $table = 'logistics_return_pickup_logs';
    protected $guarded = [];

    public function pickup(): BelongsTo { return $this->belongsTo(ReturnPickup::class, 'return_pickup_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
