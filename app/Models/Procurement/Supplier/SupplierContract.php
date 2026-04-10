<?php
// backend/app/Models/Procurement/Supplier/SupplierContract.php

namespace App\Models\Procurement\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Hr\Employee;

class SupplierContract extends Model
{
    protected $fillable = [
        'contract_number',
        'store_id',
        'supplier_id',
        'contract_title',
        'start_date',
        'end_date',
        'contract_type',
        'minimum_order_value',
        'discount_percentage',
        'tax_rate',
        'is_tax_exempt',
        'tax_note',
        'terms_conditions',
        'contract_file_path',
        'status',
        'rejection_reason',
        'rejected_by_user_id',
        'rejected_at',
        'created_by',
        'termination_request_status',
        'termination_requested_by_user_id',
        'termination_requested_by_type',
        'termination_request_reason',
        'termination_requested_at',
        'termination_response_by_user_id',
        'termination_response_notes',
        'termination_responded_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'minimum_order_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_tax_exempt' => 'boolean',
        'rejected_at' => 'datetime',
        'termination_requested_at' => 'datetime',
        'termination_responded_at' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date->diffInDays(now()) <= $days && $this->isActive();
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, $this->end_date->diffInDays(now()));
    }

    public function getDiscountedPrice(float $price): float
    {
        return $price * (1 - ($this->discount_percentage / 100));
    }
}
