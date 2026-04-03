<?php

namespace App\Models\Procurement\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;

class ProcurementInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'product_id',
        'variation_id',
        'available_qty',
        'on_order_qty',
        'received_qty',
        'pending_receive_qty',
        'status',
        'last_order_date',
        'last_receive_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_order_date' => 'datetime',
        'last_receive_date' => 'datetime',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variation (if this is a variation inventory entry)
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    /**
     * Get total quantity (all states combined)
     */
    public function getTotalQtyAttribute(): int
    {
        return $this->available_qty + $this->on_order_qty + $this->received_qty + $this->pending_receive_qty;
    }

    /**
     * Scope: Active inventories only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By store
     */
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Increase available quantity
     */
    public function increaseAvailable(int $qty, ?string $reason = null): self
    {
        $this->update([
            'available_qty' => $this->available_qty + $qty,
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Increased by {$qty}. Reason: {$reason}",
        ]);
        return $this->refresh();
    }

    /**
     * Decrease available quantity
     */
    public function decreaseAvailable(int $qty, ?string $reason = null): self
    {
        $this->update([
            'available_qty' => max(0, $this->available_qty - $qty),
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Decreased by {$qty}. Reason: {$reason}",
        ]);
        return $this->refresh();
    }

    /**
     * Mark quantity as on order (when PO is created)
     */
    public function addToOrder(int $qty, ?string $poNumber = null): self
    {
        $this->update([
            'available_qty' => max(0, $this->available_qty - $qty),
            'on_order_qty' => $this->on_order_qty + $qty,
            'pending_receive_qty' => $this->pending_receive_qty + $qty,
            'last_order_date' => now(),
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Added {$qty} to order. PO: {$poNumber}",
        ]);
        return $this->refresh();
    }

    /**
     * Mark quantity as received (when GR is created)
     */
    public function addToReceived(int $qty, ?string $grNumber = null): self
    {
        $this->update([
            'on_order_qty' => max(0, $this->on_order_qty - $qty),
            'pending_receive_qty' => max(0, $this->pending_receive_qty - $qty),
            'received_qty' => $this->received_qty + $qty,
            'last_receive_date' => now(),
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Received {$qty}. GR: {$grNumber}",
        ]);
        return $this->refresh();
    }

    /**
     * Cancel order (when PO is cancelled)
     */
    public function cancelOrder(int $qty, ?string $poNumber = null): self
    {
        $this->update([
            'available_qty' => $this->available_qty + $qty,
            'on_order_qty' => max(0, $this->on_order_qty - $qty),
            'pending_receive_qty' => max(0, $this->pending_receive_qty - $qty),
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Cancelled {$qty} from order. PO: {$poNumber}",
        ]);
        return $this->refresh();
    }
}
