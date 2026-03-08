<?php
// backend/app/Models/Procurement/Receiving/GoodsReceipt.php

namespace App\Models\Procurement\Receiving;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Store\Branch;
use App\Models\Hr\Employee;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'branch_id',
        'receipt_date',
        'receipt_time',
        'receipt_status',
        'received_by',
        'verified_by',
        'delivery_note_number',
        'vehicle_number',
        'driver_name',
        'discrepancy_notes',
        'quality_notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'receipt_time' => 'datetime',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    // Scopes
    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('receipt_date', [$start, $end]);
    }

    public function scopeWithDiscrepancies($query)
    {
        return $query->whereIn('receipt_status', ['partial', 'damaged', 'rejected']);
    }

    // Helper Methods
    public function hasDiscrepancies(): bool
    {
        return in_array($this->receipt_status, ['partial', 'damaged', 'rejected']);
    }

    public function getTotalItemsReceivedAttribute(): int
    {
        return $this->items->sum('quantity_received');
    }

    public function getTotalItemsDamagedAttribute(): int
    {
        return $this->items->sum('quantity_damaged');
    }

    public function verify(int $employeeId): void
    {
        $this->update([
            'verified_by' => $employeeId,
        ]);
    }
}