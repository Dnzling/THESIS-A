<?php
// backend/app/Models/Inventory/StockReturn.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Procurement\Supplier;
use App\Models\Hr\Employee;

class StockReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'return_number',
        'store_id',
        'from_branch_id',
        'supplier_id',
        'to_branch_id',
        'return_type',
        'status',
        'approval_policy_used',
        'total_value',
        'return_cost',
        'cost_calculation_notes',
        'requested_date',
        'approved_date',
        'shipped_date',
        'received_date',
        'expected_return_date',
        'requested_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'vehicle_type',
        'driver_name',
        'driver_contact',
        'tracking_number',
        'return_reason',
        'reason_details',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'total_value' => 'decimal:2',
        'return_cost' => 'decimal:2',
        'requested_date' => 'date',
        'approved_date' => 'date',
        'shipped_date' => 'date',
        'received_date' => 'date',
        'expected_return_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReturnItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'requested']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['received', 'cancelled']);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('return_type', $type);
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('return_reason', $reason);
    }
}
