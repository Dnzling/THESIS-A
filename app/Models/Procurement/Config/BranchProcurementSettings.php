<?php
// backend/app/Models/Procurement/Config/BranchProcurementSettings.php

namespace App\Models\Procurement\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Branch;
use App\Models\Store\Store;

class BranchProcurementSettings extends Model
{
    protected $fillable = [
        'branch_id',
        'store_id',
        'use_custom_settings',
        'custom_rfq_threshold',
        'custom_approval_tiers',
        'custom_transfer_policy',
        'monthly_procurement_budget',
        'annual_procurement_budget',
        'single_po_limit',
        'branch_authority_limit',
    ];

    protected $casts = [
        'use_custom_settings' => 'boolean',
        'custom_rfq_threshold' => 'decimal:2',
        'custom_approval_tiers' => 'array',
        'monthly_procurement_budget' => 'decimal:2',
        'annual_procurement_budget' => 'decimal:2',
        'single_po_limit' => 'decimal:2',
        'branch_authority_limit' => 'decimal:2',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Helper Methods
    public function getEffectiveRFQThreshold(): float
    {
        if ($this->use_custom_settings && $this->custom_rfq_threshold) {
            return $this->custom_rfq_threshold;
        }

        return $this->store->procurementSettings->rfq_threshold_amount ?? 50000;
    }

    public function getEffectiveApprovalTiers(): array
    {
        if ($this->use_custom_settings && $this->custom_approval_tiers) {
            return $this->custom_approval_tiers;
        }

        return $this->store->procurementSettings->approval_tiers ?? [];
    }

    public function isWithinBudget(float $amount, string $period = 'single'): bool
    {
        switch ($period) {
            case 'single':
                return !$this->single_po_limit || $amount <= $this->single_po_limit;
            case 'monthly':
                return !$this->monthly_procurement_budget || $amount <= $this->monthly_procurement_budget;
            case 'annual':
                return !$this->annual_procurement_budget || $amount <= $this->annual_procurement_budget;
            default:
                return true;
        }
    }
}