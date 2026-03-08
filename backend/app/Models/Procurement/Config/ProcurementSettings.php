<?php
// backend/app/Models/Procurement/Config/ProcurementSettings.php

namespace App\Models\Procurement\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;

class ProcurementSettings extends Model
{
    protected $fillable = [
        'store_id',
        'rfq_policy',
        'rfq_threshold_amount',
        'rfq_minimum_suppliers',
        'rfq_response_days',
        'rfq_skip_if_contract',
        'approval_tiers',
        'allow_branch_overrides',
        'transfer_approval_policy',
        'transfer_cost_method',
        'transfer_fixed_fee',
        'transfer_cost_per_km',
        'transfer_value_percentage',
        'transfer_approval_threshold',
        'auto_evaluate_suppliers',
        'min_orders_for_rating',
        'default_payment_terms',
    ];

    protected $casts = [
        'rfq_threshold_amount' => 'decimal:2',
        'rfq_minimum_suppliers' => 'integer',
        'rfq_response_days' => 'integer',
        'rfq_skip_if_contract' => 'boolean',
        'approval_tiers' => 'array',
        'allow_branch_overrides' => 'boolean',
        'transfer_fixed_fee' => 'decimal:2',
        'transfer_cost_per_km' => 'decimal:2',
        'transfer_value_percentage' => 'decimal:2',
        'transfer_approval_threshold' => 'decimal:2',
        'auto_evaluate_suppliers' => 'boolean',
        'min_orders_for_rating' => 'integer',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Helper Methods
    public function shouldRequireRFQ(float $amount, bool $hasContract = false): bool
    {
        switch ($this->rfq_policy) {
            case 'always':
                return true;
            case 'never':
                return false;
            case 'amount_based':
                return $amount >= $this->rfq_threshold_amount;
            case 'contract_based':
                return !$hasContract;
            default:
                return false;
        }
    }

    public function getApprovalTierForAmount(float $amount): ?array
    {
        if (!$this->approval_tiers) {
            return null;
        }

        foreach ($this->approval_tiers as $tier) {
            if ($amount <= $tier['max_amount']) {
                return $tier;
            }
        }

        return end($this->approval_tiers);
    }

    public function calculateTransferCost(?float $distance = null, ?float $goodsValue = null): float
    {
        switch ($this->transfer_cost_method) {
            case 'none':
                return 0;
            case 'fixed_fee':
                return (float) $this->transfer_fixed_fee;
            case 'distance_based':
                return ($distance ?? 0) * $this->transfer_cost_per_km;
            case 'value_percentage':
                return ($goodsValue ?? 0) * ($this->transfer_value_percentage / 100);
            default:
                return 0;
        }
    }
}