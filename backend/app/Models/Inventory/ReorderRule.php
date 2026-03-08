<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Store;
use App\Models\Store\Branch;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Category;

class ReorderRule extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_reorder_rules';

    protected $fillable = [
        'store_id',
        'product_id',
        'category_id',
        'branch_id',
        'reorder_point',
        'reorder_quantity',
        'maximum_stock',
        'safety_stock',
        'auto_reorder_enabled',
        'auto_reorder_days',
        'is_active',
    ];

    protected $casts = [
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'maximum_stock' => 'integer',
        'safety_stock' => 'integer',
        'auto_reorder_enabled' => 'boolean',
        'auto_reorder_days' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoReorder($query)
    {
        return $query->where('auto_reorder_enabled', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId)->orWhereNull('branch_id');
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId)->orWhereNull('category_id');
    }

    // Helper Methods
    /**
     * Check if reordering is needed based on current stock
     */
    public function shouldReorder(int $currentStock): bool
    {
        return !$this->auto_reorder_enabled || $currentStock <= $this->reorder_point;
    }

    /**
     * Get the quantity to reorder
     */
    public function getReorderQuantity(): int
    {
        return $this->reorder_quantity;
    }

    /**
     * Get quantity to bring stock to maximum
     */
    public function getQuantityToMaximum(int $currentStock): int
    {
        if (!$this->maximum_stock) {
            return $this->reorder_quantity;
        }
        return max(0, $this->maximum_stock - $currentStock);
    }

    /**
     * Check if stock is below safety level
     */
    public function isBelowSafetyStock(int $currentStock): bool
    {
        return $currentStock <= $this->safety_stock;
    }

    /**
     * Check if stock exceeds maximum
     */
    public function exceedsMaximum(int $currentStock): bool
    {
        return $this->maximum_stock && $currentStock > $this->maximum_stock;
    }

    /**
     * Check if auto-reorder is due
     */
    public function isDueForAutoReorder(\Carbon\Carbon $lastReorderDate): bool
    {
        if (!$this->auto_reorder_enabled || !$this->auto_reorder_days) {
            return false;
        }
        return now()->diffInDays($lastReorderDate) >= $this->auto_reorder_days;
    }

    /**
     * Determine rule level (product, category, or store default)
     */
    public function getRuleLevel(): string
    {
        if ($this->product_id) {
            return 'product';
        } elseif ($this->category_id) {
            return 'category';
        }
        return 'store_default';
    }
}
