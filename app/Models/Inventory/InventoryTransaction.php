<?php
// backend/app/Models/Inventory/InventoryTransaction.php

namespace App\Models\Inventory;

use App\Models\Core\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;
use App\Services\Core\ApprovalEngine;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'store_id',
        'branch_id',
        'product_id',
        'variation_id',
        'transaction_type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'related_branch_id',
        'reference_type',
        'reference_id',
        'notes',
        'unit_cost',
        'total_value',
        'requires_approval',
        'approval_status',
        'approval_workflow_id',
        'approved_by',
        'approved_at',
        'created_by',
        'transaction_date',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'transaction_date' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function relatedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'related_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    // Polymorphic reference (PO, Sale, Transfer, etc.)
    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function approvalWorkflow(): MorphOne
    {
        return $this->morphOne(ApprovalWorkflow::class, 'workflowable');
    }

    public function approvalWorkflows(): MorphMany
    {
        return $this->morphMany(ApprovalWorkflow::class, 'workflowable');
    }

    /**
     * Process approval based on dynamic rule engine.
     */
    public function processApproval(string $action, $user, int $storeId): array
    {
        /** @var ApprovalEngine $engine */
        $engine = app(ApprovalEngine::class);
        return $engine->process($this, $action, $user, $storeId);
    }

    public function needsApproval(): bool
    {
        return (bool) $this->requires_approval;
    }

    public function isApproved(): bool
    {
        return in_array((string) $this->approval_status, ['approved', 'auto_approved'], true);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }

    public function scopePurchases($query)
    {
        return $query->where('transaction_type', 'purchase');
    }

    public function scopeSales($query)
    {
        return $query->where('transaction_type', 'sale');
    }

    public function scopeTransfers($query)
    {
        return $query->whereIn('transaction_type', ['transfer_in', 'transfer_out']);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
            ->whereIn('approval_status', ['pending', 'rejected']);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('approval_status', ['approved', 'auto_approved']);
    }
}