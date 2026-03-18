<?php

namespace App\Models\Procurement\SupplierPortal;

use App\Models\Procurement\Supplier\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDeliveryTemplate extends Model
{
    protected $fillable = [
        'supplier_portal_id',
        'supplier_id',
        'created_by',
        'truck_brand',
        'truck_type',
        'wheel_count',
        'plate_number',
        'driver_name',
        'driver_contact',
        'cost_per_km',
    ];

    protected $casts = [
        'wheel_count' => 'integer',
        'cost_per_km' => 'decimal:2',
    ];

    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
