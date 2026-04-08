<?php

namespace App\Models\Procurement\SupplierPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;

class SupplierRFQFeedback extends Model
{
    protected $table = 'supplier_rfq_feedbacks';

    protected $fillable = [
        'supplier_portal_id',
        'rfq_id',
        'rfq_item_id',
        'quoted_price',
        'tax_rate',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'submitted_at',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function supplierPortal(): BelongsTo
    {
        return $this->belongsTo(SupplierPortal::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id', 'id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id', 'id');
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(SupplierRFQNegotiation::class, 'supplier_rfq_feedback_id');
    }

    // Scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('submitted_at', 'desc');
    }

    // Helper Methods
    public function getStatus(): string
    {
        if ($this->rfq->status === 'closed') {
            return 'Closed';
        }
        return 'Pending';
    }
}
