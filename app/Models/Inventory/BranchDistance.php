<?php
// backend/app/Models/Inventory/BranchDistance.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;

class BranchDistance extends Model
{
    protected $fillable = [
        'store_id',
        'from_branch_id',
        'to_branch_id',
        'distance_km',
        'estimated_travel_time_minutes',
        'route_type',
        'auto_calculated',
        'last_calculated_at',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'estimated_travel_time_minutes' => 'integer',
        'auto_calculated' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    // Helper Methods
    public static function getDistance(int $fromBranchId, int $toBranchId): ?float
    {
        $distance = self::where('from_branch_id', $fromBranchId)
            ->where('to_branch_id', $toBranchId)
            ->first();

        return $distance?->distance_km;
    }
}