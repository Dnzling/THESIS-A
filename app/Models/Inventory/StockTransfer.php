<?php
// backend/app/Models/Inventory/StockTransfer.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'store_id',
        'from_branch_id',
        'to_branch_id',
        'status',
        'approval_policy_used',
        'cost_method',
        'distance_km',
        'transfer_cost',
        'goods_value',
        'cost_calculation_notes',
        'requested_date',
        'sender_approved_date',
        'receiver_acknowledged_date',
        'finance_approved_date',
        'shipped_date',
        'received_date',
        'expected_delivery_date',
        'requested_by',
        'sender_approved_by',
        'receiver_acknowledged_by',
        'finance_approved_by',
        'shipped_by',
        'received_by',
        'vehicle_type',
        'driver_name',
        'driver_contact',
        'tracking_number',
        'reason',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'transfer_cost' => 'decimal:2',
        'goods_value' => 'decimal:2',
        'requested_date' => 'date',
        'sender_approved_date' => 'date',
        'receiver_acknowledged_date' => 'date',
        'finance_approved_date' => 'date',
        'shipped_date' => 'date',
        'received_date' => 'date',
        'expected_delivery_date' => 'date',
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

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function senderApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sender_approved_by');
    }

    public function receiverAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'receiver_acknowledged_by');
    }

    public function financeApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'finance_approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'requested', 'sender_approved']);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'received');
    }
}