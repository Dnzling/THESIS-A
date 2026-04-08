<?php
// backend/app/Models/Inventory/StockAdjustment.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adjustment_number',
        'store_id',
        'branch_id',
        'type',
        'status',
        'reason',
        'adjustment_date',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    // Helper Methods
    public function getTotalValueDifferenceAttribute(): float
    {
        return $this->items->sum('value_difference');
    }

    public function approve(int $approvedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }
}