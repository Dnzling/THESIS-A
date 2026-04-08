<?php
// backend/app/Models/Procurement/RFQ/RequestForQuotation.php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Store\Store;
use App\Models\Hr\Employee;
use App\Models\Procurement\Requisition\PurchaseRequisition;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;

class RequestForQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rfq_number',
        'title',
        'description',
        'issue_date',
        'rfq_type',
        'currency',
        'shipping_terms',
        'status',
        'store_id',
        'created_by',
        'instructions',
        'qualification_requirements',
        'sent_date',
        'awarded_date',
        'awarded_supplier_id',
        'awarded_quotation_id',
        'purchase_requisition_id',
        'evaluation_notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'sent_date' => 'datetime',
        'awarded_date' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function awardedToSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'awarded_supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RFQItem::class, 'rfq_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(RFQSupplier::class, 'rfq_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'rfq_id');
    }

    public function supplierPortalFeedbacks(): HasMany
    {
        return $this->hasMany(SupplierRFQFeedback::class, 'rfq_id');
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(RfqEvaluationCriterion::class, 'rfq_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RfqAttachment::class, 'rfq_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['sent', 'receiving', 'partially_approved']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['completed', 'rejected', 'cancelled', 'awarded']);
    }

    public function scopeAwarded($query)
    {
        return $query->where('status', 'awarded');
    }

    // Helper Methods
    public function isOpen(): bool
    {
        return in_array($this->status, ['sent', 'receiving', 'partially_approved'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'rejected', 'cancelled', 'awarded'], true);
    }

    public function getDaysRemainingAttribute(): int
    {
        return 0;
    }

    public function getQuotationsReceivedCountAttribute(): int
    {
        return $this->quotations()->where('status', 'submitted')->count();
    }

    public function getSuppliersInvitedCountAttribute(): int
    {
        return $this->suppliers()->count();
    }

    public function calculateAveragePrice(): float
    {
        $quotations = $this->quotations()->where('status', 'submitted')->get();
        if ($quotations->isEmpty()) {
            return 0;
        }
        return round($quotations->sum('total_price') / $quotations->count(), 2);
    }

    public function getLowestPrice(): ?float
    {
        return $this->quotations()->where('status', 'submitted')->min('total_price');
    }

    public function getHighestPrice(): ?float
    {
        return $this->quotations()->where('status', 'submitted')->max('total_price');
    }

    public function awardedQuotation()
    {
        return $this->belongsTo(SupplierQuotation::class, 'awarded_quotation_id');
    }

    public function inviteSupplier(int $supplierId): void
    {
        $this->suppliers()->create([
            'supplier_id' => $supplierId,
            'status' => 'pending',
            'invited_at' => now(),
        ]);
    }

    public function awardToSupplier(int $supplierId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'awarded',
            'awarded_supplier_id' => $supplierId,
            'awarded_date' => now(),
            'evaluation_notes' => $notes,
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
