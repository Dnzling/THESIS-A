<?php
// backend/app/Models/Procurement/Requisition/PurchaseRequisition.php

namespace App\Models\Procurement\Requisition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\RFQ\RequestForQuotation;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pr_number',
        'store_id',
        'branch_id',
        'requisition_type',
        'status',
        'estimated_amount',
        'procurement_route',
        'required_approvals',
        'approval_chain',
        'required_date',
        'reason',
        'priority',
        'requested_by',
        'submitted_at',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'required_approvals' => 'array',
        'approval_chain' => 'array',
        'required_date' => 'date',
        'priority' => 'integer',
        'submitted_at' => 'datetime',
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(RequestForQuotation::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted', 'warehouse_approved']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 1);
    }

    public function scopeByRoute($query, string $route)
    {
        return $query->where('procurement_route', $route);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'warehouse_approved']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'warehouse_approved';
    }

    public function addApproval(string $role, int $userId, string $userName, ?string $notes = null): void
    {
        $chain = $this->approval_chain ?? [];
        
        $chain[] = [
            'role' => $role,
            'user_id' => $userId,
            'user_name' => $userName,
            'approved_at' => now()->toDateTimeString(),
            'notes' => $notes,
        ];

        $this->approval_chain = $chain;
        $this->save();
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function getTotalEstimatedAmountAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity_requested * ($item->estimated_unit_cost ?? 0);
        });
    }
}