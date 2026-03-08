<?php
// backend/app/Models/Procurement/PurchaseOrder/PurchaseOrder.php

namespace App\Models\Procurement\PurchaseOrder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\SupplierQuotation;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Supplier\SupplierPayment;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number',
        'store_id',
        'branch_id',
        'supplier_id',
        'purchase_requisition_id',
        'rfq_id',
        'supplier_quotation_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'approval_tier_level',
        'required_approvers',
        'approvals_received',
        'rejection_details',
        'rfq_required',
        'payment_status',
        'payment_terms',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'payment_due_date',
        'created_by',
        'notes',
        'terms_conditions',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approval_tier_level' => 'integer',
        'required_approvers' => 'array',
        'approvals_received' => 'array',
        'rejection_details' => 'array',
        'rfq_required' => 'boolean',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'payment_due_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function supplierQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval', 'partially_approved']);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['fully_approved', 'finance_approved']);
    }

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function scopeReceived($query)
    {
        return $query->whereIn('status', ['received', 'partially_received']);
    }

    // Helper Methods
    public function addApproval(string $role, int $userId, string $userName, ?string $notes = null): void
    {
        $approvals = $this->approvals_received ?? [];
        
        $approvals[] = [
            'approver_role' => $role,
            'approver_id' => $userId,
            'approver_name' => $userName,
            'approved_at' => now()->toDateTimeString(),
            'notes' => $notes,
        ];

        $this->approvals_received = $approvals;
        $this->save();

        // Check if all approvals received
        if ($this->isFullyApproved()) {
            $this->update(['status' => 'fully_approved']);
        } else {
            $this->update(['status' => 'partially_approved']);
        }
    }

    public function isFullyApproved(): bool
    {
        $required = $this->required_approvers ?? [];
        $received = collect($this->approvals_received ?? [])->pluck('approver_role')->toArray();

        foreach ($required as $role) {
            if (!in_array($role, $received)) {
                return false;
            }
        }

        return true;
    }

    public function reject(string $role, int $userId, string $reason): void
    {
        $this->rejection_details = [
            'rejected_by_role' => $role,
            'rejected_by_id' => $userId,
            'rejected_at' => now()->toDateTimeString(),
            'reason' => $reason,
        ];

        $this->status = 'rejected';
        $this->save();
    }

    public function sendToSupplier(): void
    {
        $this->update([
            'status' => 'ordered',
            'order_date' => now(),
        ]);

        // Update supplier stats
        $this->supplier->incrementOrders($this->total_amount);
    }

    public function getQuantityReceivedAttribute(): int
    {
        return $this->items->sum('quantity_received');
    }

    public function getQuantityOrderedAttribute(): int
    {
        return $this->items->sum('quantity_ordered');
    }

    public function getReceivalPercentageAttribute(): float
    {
        if ($this->quantity_ordered === 0) {
            return 0;
        }

        return round(($this->quantity_received / $this->quantity_ordered) * 100, 2);
    }

    public function isOverdue(): bool
    {
        return $this->expected_delivery_date < now() && !in_array($this->status, ['received', 'cancelled']);
    }
}