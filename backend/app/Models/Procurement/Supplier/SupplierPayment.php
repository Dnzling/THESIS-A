<?php
// backend/app/Models/Procurement/Supplier/SupplierPayment.php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Hr\Employee;

class SupplierPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'store_id',
        'purchase_order_id',
        'supplier_id',
        'payment_amount',
        'payment_method',
        'status',
        'payment_date',
        'reference_number',
        'bank_name',
        'account_number',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('payment_date', [$start, $end]);
    }

    // Helper Methods
    public function approve(int $employeeId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $employeeId,
            'approved_at' => now(),
        ]);
    }

    public function process(int $employeeId): void
    {
        $this->update([
            'status' => 'completed',
            'processed_by' => $employeeId,
            'processed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}