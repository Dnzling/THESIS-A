<?php
// backend/app/Models/Procurement/RFQ/RFQSupplier.php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Procurement\Supplier\Supplier;

class RFQSupplier extends Model
{
    protected $table = 'rfq_suppliers';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'status',
        'sent_at',
        'viewed_at',
        'submitted_at',
        'decline_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Helper Methods
    public function markAsViewed(): void
    {
        $this->update([
            'status' => 'viewed',
            'viewed_at' => now(),
        ]);
    }

    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function decline(string $reason): void
    {
        $this->update([
            'status' => 'declined',
            'decline_reason' => $reason,
        ]);
    }

    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->submitted_at || !$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffInHours($this->submitted_at);
    }
}