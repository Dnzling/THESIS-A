<?php

namespace App\Models\Procurement\Shipping;

use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Branch;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Ecommerce\EcommerceDeliveryVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderShipment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'created_by',
        'driver_employee_id',
        'driver_user_id',
        'vehicle_id',
        'truck_number',
        'truck_brand',
        'truck_type',
        'wheel_count',
        'plate_number',
        'driver_name',
        'driver_contact',
        'origin_address',
        'destination_address',
        'current_latitude',
        'current_longitude',
        'current_address',
        'distance_km',
        'cost_per_km',
        'shipping_cost',
        'tax_rate',
        'expected_delivery_date',
        'dispatched_at',
        'delivered_at',
        'status',
    ];

    protected $casts = [
        'wheel_count' => 'integer',
        'current_latitude' => 'decimal:7',
        'current_longitude' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function driverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_employee_id');
    }

    public function driverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(EcommerceDeliveryVehicle::class, 'vehicle_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PurchaseOrderDeliveryLog::class, 'shipment_id');
    }
}
