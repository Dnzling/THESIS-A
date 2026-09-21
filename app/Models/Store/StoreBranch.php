<?php

namespace App\Models\Store;

use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Hr\Employee;
use App\Models\Inventory\BranchInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch-facing model used by the Store module.
 *
 * It intentionally uses the existing branches table so branch identity and
 * inventory remain a single source of truth.
 */
class StoreBranch extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'name', 'address', 'barangay', 'city', 'province', 'contact_number',
        'email', 'latitude', 'longitude', 'geofence_radius_m',
        'geofence_enabled', 'logo_path',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'geofence_radius_m' => 'integer',
        'geofence_enabled' => 'boolean',
        'is_main_branch' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'branch_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(BranchInventory::class, 'branch_id');
    }

    public function ecommerceOrders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class, 'assigned_branch_id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.ltrim($this->logo_path, '/')) : null;
    }
}
