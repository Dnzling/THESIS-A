<?php
// backend/app/Models/Inventory/ReorderRule.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Carbon\Carbon;

class ReorderRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'branch_id',
        'rule_type',
        'trigger_type',
        'reorder_point',
        'reorder_quantity',
        'lead_time_days',
        'safety_stock',
        'maximum_stock',
        'economic_order_quantity',
        'priority',
        'auto_generate_po',
        'supplier_preferences',
        'seasonal_adjustments',
        'is_active',
        'last_triggered_at',
        'next_review_date',
        'notes',
    ];

    protected $casts = [
        'reorder_point' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'lead_time_days' => 'integer',
        'safety_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'economic_order_quantity' => 'decimal:2',
        'auto_generate_po' => 'boolean',
        'supplier_preferences' => 'array',
        'seasonal_adjustments' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'next_review_date' => 'datetime',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('rule_type', $type);
    }

    public function scopeByTrigger($query, $trigger)
    {
        return $query->where('trigger_type', $trigger);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeAutoGeneratePO($query)
    {
        return $query->where('auto_generate_po', true);
    }

    // Helper methods
    public function shouldReorder(float $currentStock): bool
    {
        if (!$this->is_active) {
            return false;
        }

        switch ($this->trigger_type) {
            case 'reorder_point':
                return $currentStock <= $this->reorder_point;
            case 'safety_stock':
                return $currentStock <= $this->safety_stock;
            case 'forecast':
                // Implement forecast-based logic
                return $this->shouldReorderByForecast($currentStock);
            case 'seasonal':
                // Implement seasonal logic
                return $this->shouldReorderBySeasonal($currentStock);
            default:
                return false;
        }
    }

    private function shouldReorderByForecast(float $currentStock): bool
    {
        // Placeholder for forecast-based reordering logic
        // This would typically involve demand forecasting algorithms
        return $currentStock <= $this->reorder_point;
    }

    private function shouldReorderBySeasonal(float $currentStock): bool
    {
        // Placeholder for seasonal reordering logic
        // This would check seasonal adjustments
        $adjustment = $this->getSeasonalAdjustment();
        $adjustedReorderPoint = $this->reorder_point * $adjustment;

        return $currentStock <= $adjustedReorderPoint;
    }

    public function getReorderQuantity(): float
    {
        if ($this->rule_type === 'demand_based' && $this->economic_order_quantity) {
            return $this->economic_order_quantity;
        }

        return $this->reorder_quantity ?? 0;
    }

    public function getSeasonalAdjustment(): float
    {
        if (!$this->seasonal_adjustments) {
            return 1.0;
        }

        $currentMonth = now()->month;
        return $this->seasonal_adjustments[$currentMonth] ?? 1.0;
    }

    public function calculateEOQ(float $annualDemand, float $orderingCost, float $holdingCost): float
    {
        if ($annualDemand <= 0 || $orderingCost <= 0 || $holdingCost <= 0) {
            return 0;
        }

        // EOQ = sqrt(2 * Annual Demand * Ordering Cost / Holding Cost)
        return sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);
    }

    public function isBelowSafetyStock(float $currentStock): bool
    {
        return $this->safety_stock && $currentStock <= $this->safety_stock;
    }

    public function exceedsMaximum(float $currentStock): bool
    {
        return $this->maximum_stock && $currentStock > $this->maximum_stock;
    }

    public function getLeadTimeInDays(): int
    {
        return $this->lead_time_days ?? 0;
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

    public function markAsTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'next_review_date' => now()->addDays(30), // Review every 30 days
        ]);
    }

    public function getPreferredSuppliers(): array
    {
        return $this->supplier_preferences ?? [];
    }

    public function needsReview(): bool
    {
        return !$this->next_review_date || $this->next_review_date->isPast();
    }

    public function getRuleDescription(): string
    {
        $description = ucfirst($this->rule_type) . ' rule';

        if ($this->trigger_type === 'reorder_point' && $this->reorder_point) {
            $description .= " - Reorder at {$this->reorder_point} units";
        }

        if ($this->auto_generate_po) {
            $description .= ' (Auto PO)';
        }

        return $description;
    }
}
