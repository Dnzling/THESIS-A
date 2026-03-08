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

class RequestForQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rfq_number',
        'store_id',
        'purchase_requisition_id',
        'title',
        'description',
        'issue_date',
        'deadline_date',
        'status',
        'created_by',
        'awarded_to_supplier_id',
        'awarded_at',
        'evaluation_notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'deadline_date' => 'date',
        'awarded_at' => 'datetime',
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
        return $this->belongsTo(Supplier::class, 'awarded_to_supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RFQItem::class, 'rfq_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'rfq_suppliers', 'rfq_id', 'supplier_id')
            ->withPivot('status', 'sent_at', 'viewed_at', 'submitted_at', 'decline_reason')
            ->withTimestamps();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'rfq_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'sent')
            ->where('deadline_date', '>=', now());
    }

    public function scopeClosed($query)
    {
        return $query->where('deadline_date', '<', now())
            ->whereIn('status', ['sent', 'quotes_received']);
    }

    public function scopeAwarded($query)
    {
        return $query->where('status', 'awarded');
    }

    // Helper Methods
    public function isOpen(): bool
    {
        return $this->status === 'sent' && $this->deadline_date >= now();
    }

    public function isClosed(): bool
    {
        return $this->deadline_date < now() || in_array($this->status, ['awarded', 'cancelled']);
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->isClosed()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->deadline_date, false));
    }

    public function getQuotationsReceivedCountAttribute(): int
    {
        return $this->quotations()->where('status', 'submitted')->count();
    }

    public function getSuppliersInvitedCountAttribute(): int
    {
        return $this->suppliers()->count();
    }

    public function inviteSupplier(int $supplierId): void
    {
        $this->suppliers()->attach($supplierId, [
            'status' => 'invited',
            'sent_at' => now(),
        ]);
    }

    public function awardToSupplier(int $supplierId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'awarded',
            'awarded_to_supplier_id' => $supplierId,
            'awarded_at' => now(),
            'evaluation_notes' => $notes,
        ]);
    }

    public function close(): void
    {
        if ($this->quotations_received_count > 0) {
            $this->update(['status' => 'quotes_received']);
        } else {
            $this->update(['status' => 'cancelled']);
        }
    }
}