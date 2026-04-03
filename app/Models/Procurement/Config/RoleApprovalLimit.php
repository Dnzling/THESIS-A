<?php
// backend/app/Models/Procurement/Config/RoleApprovalLimit.php

namespace App\Models\Procurement\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Core\Role;

class RoleApprovalLimit extends Model
{
    protected $fillable = [
        'store_id',
        'role_id',
        'max_approval_amount',
        'can_approve_rfq',
        'can_create_po',
        'can_approve_po',
        'can_approve_transfers',
        'requires_dual_approval',
        'can_manage_suppliers',
        'can_negotiate_prices',
    ];

    protected $casts = [
        'max_approval_amount' => 'decimal:2',
        'can_approve_rfq' => 'boolean',
        'can_create_po' => 'boolean',
        'can_approve_po' => 'boolean',
        'can_approve_transfers' => 'boolean',
        'requires_dual_approval' => 'boolean',
        'can_manage_suppliers' => 'boolean',
        'can_negotiate_prices' => 'boolean',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // Helper Methods
    public function canApproveAmount(float $amount): bool
    {
        return $amount <= $this->max_approval_amount;
    }

    public function hasPermission(string $permission): bool
    {
        return match($permission) {
            'approve_rfq' => $this->can_approve_rfq,
            'create_po' => $this->can_create_po,
            'approve_po' => $this->can_approve_po,
            'approve_transfers' => $this->can_approve_transfers,
            'manage_suppliers' => $this->can_manage_suppliers,
            'negotiate_prices' => $this->can_negotiate_prices,
            default => false,
        };
    }
}