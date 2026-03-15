<?php

namespace App\Models\Procurement\StockOrder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Store\Store;
use App\Models\Inventory\BranchInventory;
use App\Models\Hr\Employee;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;

class StockOrderRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'store_id',
        'branch_inventory_id',
        'requested_quantity',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_date',
        'conversion_date',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_date' => 'datetime',
        'conversion_date' => 'datetime',
    ];

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the store this request belongs to
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the branch inventory item that triggered this request
     */
    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class);
    }

    /**
     * Get who created this request
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Get who approved this request
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get all purchase orders created from this stock request
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // ==================== SCOPES ====================

    /**
     * Filter pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filter approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Filter converted to PO
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted_to_po');
    }

    /**
     * Filter by branch
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->whereHas('branchInventory', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    /**
     * Filter by product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->whereHas('branchInventory', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        });
    }

    // ==================== ACTIONS ====================

    /**
     * Approve this stock order request
     */
    public function approve($approverId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_date' => now(),
        ]);
    }

    /**
     * Reject this request
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Mark as converted to PO
     */
    public function markConverted()
    {
        $this->update([
            'status' => 'converted_to_po',
            'conversion_date' => now(),
        ]);
    }

    /**
     * Mark as partially ordered (multiple POs)
     */
    public function markPartiallyOrdered()
    {
        $this->update([
            'status' => 'partially_ordered',
            'conversion_date' => now(),
        ]);
    }

    /**
     * Cancel this request
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    // ==================== HELPERS ====================

    /**
     * Get the branch that needs this stock
     */
    public function getBranch()
    {
        return $this->branchInventory?->branch;
    }

    /**
     * Get the product needed
     */
    public function getProduct()
    {
        return $this->branchInventory?->product;
    }

    /**
     * Get the product variation (if applicable)
     */
    public function getVariation()
    {
        return $this->branchInventory?->variation;
    }

    /**
     * Check if request can be converted to PO
     */
    public function canConvertToPO(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Get suppliers that can supply this product
     */
    public function getAvailableSuppliers()
    {
        $product = $this->getProduct();
        if (!$product) {
            return collect();
        }

        return $product->suppliers()->where('status', 'active')->get();
    }
}
