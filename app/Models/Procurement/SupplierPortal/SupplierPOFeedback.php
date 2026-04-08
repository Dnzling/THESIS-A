<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;

class SupplierPOFeedback extends Model
{
    protected $table = 'supplier_po_feedbacks';

    protected $fillable = [
        'supplier_portal_id',
        'purchase_order_id',
        'response',
        'rejection_reason',
        'receipt_status',
        'expected_delivery_date',
        'delivery_quantity',
        'delivery_notes',
        'receipt_confirmed_at',
        'submitted_at',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'receipt_confirmed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'delivery_quantity' => 'integer',
    ];

    // Relationships
    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    // Scopes
    public function scopeAccepted($query)
    {
        return $query->where('response', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('response', 'rejected');
    }

    public function scopeReceiptConfirmed($query)
    {
        return $query->where('receipt_status', 'confirmed');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('submitted_at', 'desc');
    }

    // Helper Methods
    public function hasAccepted(): bool
    {
        return $this->response === 'accepted';
    }

    public function isReceiptConfirmed(): bool
    {
        return $this->receipt_status === 'confirmed';
    }

    public function canUpdateDeliverySchedule(): bool
    {
        return $this->response === 'accepted' && !$this->isReceiptConfirmed();
    }
}
