<?php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Procurement\Supplier\Supplier;

class SupplierQuotation extends Model
{
    protected $fillable = [
        'quotation_number',
        'rfq_id',
        'supplier_id',
        'total_price',
        'delivery_days',
        'payment_terms',
        'validity_date',
        'price_per_unit',
        'notes',
        'status',
        'submitted_date',
        'score',
        'rank',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'delivery_days' => 'integer',
        'validity_date' => 'date',
        'submitted_date' => 'datetime',
        'score' => 'decimal:2',
        'rank' => 'integer',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class, 'quotation_id');
    }

    // Scopes
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now());
    }

    // Helper Methods
    public function isValid(): bool
    {
        return $this->valid_until >= now();
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);

        // Mark supplier as awarded in RFQ
        $this->rfq->awardToSupplier($this->supplier_id);

        // Reject other quotations
        $this->rfq->quotations()
            ->where('id', '!=', $this->id)
            ->update(['status' => 'rejected']);
    }

    public function reject(?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'evaluation_notes' => $notes,
        ]);
    }

    public function evaluate(float $score, ?string $notes = null): void
    {
        $this->update([
            'evaluation_score' => $score,
            'evaluation_notes' => $notes,
            'status' => 'under_review',
        ]);
    }

    public function calculateScore(array $criteria): float
    {
        // Criteria example: ['price' => 40, 'delivery' => 30, 'quality' => 20, 'history' => 10]
        $scores = [];

        // Price score (lower is better)
        $lowestPrice = $this->rfq->quotations()->min('total_amount');
        $scores['price'] = ($lowestPrice / $this->total_amount) * ($criteria['price'] ?? 40);

        // Delivery score (faster is better)
        $fastestDelivery = $this->rfq->quotations()->min('delivery_days');
        $scores['delivery'] = ($fastestDelivery / max($this->delivery_days, 1)) * ($criteria['delivery'] ?? 30);

        // Supplier rating score
        $scores['quality'] = ($this->supplier->rating / 5) * ($criteria['quality'] ?? 20);

        // Supplier history score
        $scores['history'] = ($this->supplier->on_time_delivery_rate / 100) * ($criteria['history'] ?? 10);

        return array_sum($scores);
    }
}
