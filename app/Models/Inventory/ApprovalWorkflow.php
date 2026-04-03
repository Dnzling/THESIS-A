<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;

class ApprovalWorkflow extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_approval_workflows';

    protected $fillable = [
        'store_id',
        'workflow_type',
        'approval_stages',
        'minimum_amount_trigger',
        'auto_approve_below_amount',
        'is_active',
    ];

    protected $casts = [
        'approval_stages' => 'array',
        'minimum_amount_trigger' => 'decimal:2',
        'auto_approve_below_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('workflow_type', $type);
    }

    // Helper Methods
    /**
     * Get approval stages in order
     */
    public function getApprovalStages(): array
    {
        $stages = $this->approval_stages ?? [];
        usort($stages, fn($a, $b) => $a['order'] <=> $b['order']);
        return $stages;
    }

    /**
     * Check if workflow auto-approves for given amount
     */
    public function autoApprovesForAmount(float $amount): bool
    {
        if (!$this->auto_approve_below_amount) {
            return false;
        }
        return $amount < $this->auto_approve_below_amount;
    }

    /**
     * Check if amount requires approval
     */
    public function requiresApprovalForAmount(float $amount): bool
    {
        if (!$this->minimum_amount_trigger) {
            return true; // Always requires approval if no minimum
        }
        return $amount >= $this->minimum_amount_trigger;
    }

    /**
     * Get required approval roles for workflow
     */
    public function getRequiredRoles(): array
    {
        $stages = $this->getApprovalStages();
        return array_map(fn($stage) => $stage['role'], array_filter($stages, fn($s) => $s['required'] ?? true));
    }

    /**
     * Get number of required approvals
     */
    public function getRequiredApprovalsCount(): int
    {
        $stages = $this->getApprovalStages();
        return count(array_filter($stages, fn($s) => $s['required'] ?? true));
    }

    /**
     * Check if workflow requires specific role
     */
    public function requiresRole(string $role): bool
    {
        $stages = $this->getApprovalStages();
        return in_array($role, array_map(fn($s) => $s['role'], $stages));
    }
}
