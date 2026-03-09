<?php
// backend/app/Models/Inventory/ReorderSuggestion.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use App\Models\Employee;
use Carbon\Carbon;

class ReorderSuggestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reorder_rule_id',
        'product_id',
        'branch_id',
        'suggestion_type',
        'current_stock',
        'suggested_quantity',
        'estimated_cost',
        'priority',
        'status',
        'reason',
        'metadata',
        'suggested_at',
        'approved_at',
        'implemented_at',
        'approved_by',
        'implemented_by',
        'approval_notes',
        'implementation_notes',
        'valid_until',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'suggested_quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'metadata' => 'array',
        'suggested_at' => 'datetime',
        'approved_at' => 'datetime',
        'implemented_at' => 'datetime',
        'valid_until' => 'datetime',
    ];

    // Relationships
    public function reorderRule(): BelongsTo
    {
        return $this->belongsTo(ReorderRule::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function implementer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'implemented_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeImplemented($query)
    {
        return $query->where('status', 'implemented');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('suggestion_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>', now());
        });
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isImplemented(): bool
    {
        return $this->status === 'implemented';
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function canBeImplemented(): bool
    {
        return $this->isApproved() && !$this->isExpired();
    }

    public function approve(?int $approvedBy = null, ?string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
        ]);

        return true;
    }

    public function reject(?string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approval_notes' => $notes,
        ]);

        return true;
    }

    public function implement(?int $implementedBy = null, ?string $notes = null): bool
    {
        if (!$this->canBeImplemented()) {
            return false;
        }

        $this->update([
            'status' => 'implemented',
            'implemented_at' => now(),
            'implemented_by' => $implementedBy,
            'implementation_notes' => $notes,
        ]);

        return true;
    }

    public function cancel(?string $notes = null): bool
    {
        if (in_array($this->status, ['implemented', 'cancelled'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'implementation_notes' => $notes,
        ]);

        return true;
    }

    public function getPriorityLevel(): int
    {
        return match($this->priority) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 2,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'implemented' => 'blue',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getDaysSinceSuggested(): int
    {
        return $this->suggested_at->diffInDays(now());
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }

    public function calculateEstimatedCost(float $unitCost): float
    {
        $cost = $this->suggested_quantity * $unitCost;
        $this->update(['estimated_cost' => $cost]);

        return $cost;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMetadataValue(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
    }

    public function getSuggestionSummary(): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product->name,
            'branch_name' => $this->branch->name,
            'type' => $this->suggestion_type,
            'current_stock' => $this->current_stock,
            'suggested_quantity' => $this->suggested_quantity,
            'estimated_cost' => $this->estimated_cost,
            'priority' => $this->priority,
            'status' => $this->status,
            'reason' => $this->reason,
            'suggested_at' => $this->suggested_at->format('Y-m-d H:i:s'),
            'days_old' => $this->getDaysSinceSuggested(),
            'expires_in_days' => $this->getDaysUntilExpiry(),
        ];
    }
}
