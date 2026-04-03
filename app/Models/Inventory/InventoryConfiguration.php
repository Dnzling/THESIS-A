<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;

class InventoryConfiguration extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_configurations';

    protected $fillable = [
        'store_id',
        'model_type',
        'enable_transfer_approvals',
        'enable_finance_approval',
        'enable_auto_alerts',
        'enable_cost_tracking',
        'enable_physical_counts',
        'main_branch_id',
        'warehouse_branch_ids',
        'default_reorder_point',
        'default_reorder_quantity',
        'default_safety_stock',
        'default_maximum_stock',
        'require_finance_approval_above',
        'allow_auto_transfer',
        'auto_transfer_threshold',
        'transfer_cost_model',
        'fixed_transfer_cost',
        'cost_per_km',
        'reporting_frequency',
        'include_sub_branches',
    ];

    protected $casts = [
        'model_type' => 'string',
        'enable_transfer_approvals' => 'boolean',
        'enable_finance_approval' => 'boolean',
        'enable_auto_alerts' => 'boolean',
        'enable_cost_tracking' => 'boolean',
        'enable_physical_counts' => 'boolean',
        'warehouse_branch_ids' => 'array',
        'default_reorder_point' => 'integer',
        'default_reorder_quantity' => 'integer',
        'default_safety_stock' => 'integer',
        'default_maximum_stock' => 'integer',
        'require_finance_approval_above' => 'decimal:2',
        'allow_auto_transfer' => 'boolean',
        'auto_transfer_threshold' => 'integer',
        'transfer_cost_model' => 'string',
        'fixed_transfer_cost' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function mainBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'main_branch_id');
    }

    // Helper Methods
    public function isMultiBranch(): bool
    {
        return in_array($this->model_type, ['centralized', 'distributed', 'multi_store']);
    }

    public function isCentralized(): bool
    {
        return $this->model_type === 'centralized';
    }

    public function requiresFinanceApproval(float $amount): bool
    {
        return $this->enable_finance_approval && $amount >= $this->require_finance_approval_above;
    }

    public function getTransferCostPerKm(): float
    {
        return (float) $this->cost_per_km;
    }

    public function getFixedTransferCost(): ?float
    {
        return $this->fixed_transfer_cost ? (float) $this->fixed_transfer_cost : null;
    }

    public function shouldAutoAlert(): bool
    {
        return $this->enable_auto_alerts;
    }

    public function shouldTrackCosts(): bool
    {
        return $this->enable_cost_tracking;
    }

    public function getWarehouseBranches(): array
    {
        return $this->warehouse_branch_ids ?? [];
    }
}
