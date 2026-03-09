<?php
// backend/app/Models/Inventory/StockCount.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;

class StockCount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'count_number',
        'store_id',
        'branch_id',
        'status',
        'count_type',
        'scheduled_date',
        'started_date',
        'completed_date',
        'approved_date',
        'assigned_by',
        'assigned_to',
        'supervised_by',
        'approved_by',
        'total_items_expected',
        'total_items_counted',
        'items_with_discrepancy',
        'total_value_counted',
        'total_discrepancy_value',
        'warehouse_section',
        'aisle',
        'rack',
        'shelf',
        'category_ids',
        'product_ids',
        'instructions',
        'notes',
        'approval_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_date' => 'date',
        'completed_date' => 'date',
        'approved_date' => 'date',
        'total_value_counted' => 'decimal:2',
        'total_discrepancy_value' => 'decimal:2',
        'category_ids' => 'array',
        'product_ids' => 'array',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function supervisedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervised_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function countSheets(): HasMany
    {
        return $this->hasMany(CountSheet::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('count_type', $type);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeScheduledForDate($query, $date)
    {
        return $query->where('scheduled_date', $date);
    }

    // Helper methods
    public function isEditable(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    public function canBeStarted(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_items_expected === 0) {
            return 0;
        }

        return round(($this->total_items_counted / $this->total_items_expected) * 100, 2);
    }

    public function getDiscrepancyPercentage(): float
    {
        if ($this->total_items_counted === 0) {
            return 0;
        }

        return round(($this->items_with_discrepancy / $this->total_items_counted) * 100, 2);
    }
}
