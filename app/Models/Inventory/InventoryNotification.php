<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\Hr\User;

class InventoryNotification extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_notifications';

    protected $fillable = [
        'store_id',
        'branch_id',
        'user_id',
        'notification_type',
        'entity_type',
        'entity_id',
        'title',
        'message',
        'action_required',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'action_required' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeActionRequired($query)
    {
        return $query->where('action_required', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if notification requires action
     */
    public function requiresAction(): bool
    {
        return $this->action_required && !$this->is_read;
    }

    /**
     * Get user-friendly notification type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->notification_type) {
            'low_stock_alert' => 'Low Stock Alert',
            'out_of_stock' => 'Out of Stock',
            'transfer_request' => 'Transfer Request',
            'adjustment_pending' => 'Adjustment Pending',
            'reorder_needed' => 'Reorder Needed',
            'transfer_approved' => 'Transfer Approved',
            'transfer_shipped' => 'Transfer Shipped',
            'transfer_received' => 'Transfer Received',
            'approval_required' => 'Approval Required',
            default => $this->notification_type,
        };
    }

    /**
     * Get icon for notification type
     */
    public function getTypeIcon(): string
    {
        return match ($this->notification_type) {
            'low_stock_alert' => 'alert-circle',
            'out_of_stock' => 'alert-octagon',
            'transfer_request' => 'arrow-right',
            'adjustment_pending' => 'edit',
            'reorder_needed' => 'shopping-cart',
            'transfer_approved' => 'check-circle',
            'transfer_shipped' => 'truck',
            'transfer_received' => 'package',
            'approval_required' => 'user-check',
            default => 'info',
        };
    }

    /**
     * Get color for notification type (for UI)
     */
    public function getTypeColor(): string
    {
        return match ($this->notification_type) {
            'low_stock_alert' => 'warning',
            'out_of_stock' => 'danger',
            'transfer_request' => 'info',
            'adjustment_pending' => 'info',
            'reorder_needed' => 'warning',
            'transfer_approved' => 'success',
            'transfer_shipped' => 'info',
            'transfer_received' => 'success',
            'approval_required' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get related entity instance (dynamic loading based on entity_type)
     */
    public function getEntity()
    {
        return match ($this->entity_type) {
            'stock_alert' => StockAlert::find($this->entity_id),
            'stock_transfer' => StockTransfer::find($this->entity_id),
            'stock_adjustment' => StockAdjustment::find($this->entity_id),
            default => null,
        };
    }
}
