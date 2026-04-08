<?php
// backend/app/Models/Inventory/Batch.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Carbon\Carbon;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_number',
        'product_id',
        'branch_id',
        'warehouse_location_id',
        'quantity_produced',
        'quantity_available',
        'quantity_sold',
        'quantity_reserved',
        'quantity_damaged',
        'quantity_returned',
        'unit_cost',
        'unit_price',
        'production_date',
        'expiry_date',
        'best_before_date',
        'status',
        'quality_status',
        'supplier_name',
        'supplier_batch_number',
        'notes',
        'specifications',
        'quality_test_results',
        'metadata',
    ];

    protected $casts = [
        'quantity_produced' => 'integer',
        'quantity_available' => 'integer',
        'quantity_sold' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_damaged' => 'integer',
        'quantity_returned' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'production_date' => 'date',
        'expiry_date' => 'date',
        'best_before_date' => 'date',
        'specifications' => 'array',
        'quality_test_results' => 'array',
        'metadata' => 'array',
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

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeDepleted($query)
    {
        return $query->where('status', 'depleted');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByQualityStatus($query, $qualityStatus)
    {
        return $query->where('quality_status', $qualityStatus);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeBestBeforeSoon($query, $days = 30)
    {
        return $query->where('best_before_date', '<=', now()->addDays($days))
                    ->where('best_before_date', '>', now());
    }

    public function scopeInLocation($query, $locationId)
    {
        return $query->where('warehouse_location_id', $locationId);
    }

    public function scopeHasAvailableStock($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expiry_date && $this->expiry_date->isPast());
    }

    public function isDepleted(): bool
    {
        return $this->quantity_available <= 0;
    }

    public function isExpiringSoon($days = 30): bool
    {
        return $this->expiry_date &&
               $this->expiry_date->isFuture() &&
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function isBestBeforeSoon($days = 30): bool
    {
        return $this->best_before_date &&
               $this->best_before_date->isFuture() &&
               $this->best_before_date->diffInDays(now()) <= $days;
    }

    public function hasAvailableStock(): bool
    {
        return $this->quantity_available > 0;
    }

    public function canReserve($quantity): bool
    {
        return $this->isActive() && $this->quantity_available >= $quantity;
    }

    public function canSell($quantity): bool
    {
        return $this->isActive() && $this->quantity_available >= $quantity;
    }

    public function reserveStock($quantity): bool
    {
        if (!$this->canReserve($quantity)) {
            return false;
        }

        $this->decrement('quantity_available', $quantity);
        $this->increment('quantity_reserved', $quantity);

        return true;
    }

    public function unreserveStock($quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->increment('quantity_available', $quantity);
        $this->decrement('quantity_reserved', $quantity);

        return true;
    }

    public function sellStock($quantity): bool
    {
        if (!$this->canSell($quantity)) {
            return false;
        }

        $this->decrement('quantity_available', $quantity);
        $this->increment('quantity_sold', $quantity);

        // Check if depleted
        if ($this->quantity_available <= 0) {
            $this->update(['status' => 'depleted']);
        }

        return true;
    }

    public function returnStock($quantity): bool
    {
        $this->increment('quantity_available', $quantity);
        $this->increment('quantity_returned', $quantity);

        // Update status if it was depleted
        if ($this->status === 'depleted' && $this->quantity_available > 0) {
            $this->update(['status' => 'active']);
        }

        return true;
    }

    public function markAsDamaged($quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->decrement('quantity_available', $quantity);
        $this->increment('quantity_damaged', $quantity);

        return true;
    }

    public function moveToLocation(?int $locationId, ?string $notes = null): bool
    {
        $this->update([
            'warehouse_location_id' => $locationId,
            'notes' => $notes ? ($this->notes ? $this->notes . "\nMoved: " . $notes : "Moved: " . $notes) : $this->notes,
        ]);

        return true;
    }

    public function approveQuality(): bool
    {
        if ($this->quality_status !== 'pending') {
            return false;
        }

        $this->update(['quality_status' => 'approved']);
        return true;
    }

    public function rejectQuality(): bool
    {
        if ($this->quality_status !== 'pending') {
            return false;
        }

        $this->update(['quality_status' => 'rejected']);
        return true;
    }

    public function quarantine(): bool
    {
        $this->update(['quality_status' => 'quarantined']);
        return true;
    }

    public function getTotalValue(): float
    {
        return $this->quantity_available * ($this->unit_price ?? 0);
    }

    public function getTotalCost(): float
    {
        return $this->quantity_produced * ($this->unit_cost ?? 0);
    }

    public function getProfit(): float
    {
        return $this->getTotalValue() - $this->getTotalCost();
    }

    public function getProfitMargin(): ?float
    {
        $cost = $this->getTotalCost();
        if ($cost == 0) {
            return null;
        }

        return ($this->getProfit() / $cost) * 100;
    }

    public function getStockTurnover(): ?float
    {
        $daysSinceProduction = $this->production_date->diffInDays(now());
        if ($daysSinceProduction == 0) {
            return null;
        }

        return $this->quantity_sold / $daysSinceProduction;
    }

    public function getDaysToExpiry(): ?int
    {
        return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null;
    }

    public function getDaysToBestBefore(): ?int
    {
        return $this->best_before_date ? now()->diffInDays($this->best_before_date, false) : null;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'green',
            'expired' => 'red',
            'depleted' => 'orange',
            'discontinued' => 'gray',
            default => 'gray',
        };
    }

    public function getQualityStatusColor(): string
    {
        return match($this->quality_status) {
            'approved' => 'green',
            'pending' => 'yellow',
            'rejected' => 'red',
            'quarantined' => 'orange',
            default => 'gray',
        };
    }

    public function getStockSummary(): array
    {
        return [
            'total_produced' => $this->quantity_produced,
            'available' => $this->quantity_available,
            'sold' => $this->quantity_sold,
            'reserved' => $this->quantity_reserved,
            'damaged' => $this->quantity_damaged,
            'returned' => $this->quantity_returned,
            'utilization_rate' => $this->quantity_produced > 0 ?
                round(($this->quantity_sold / $this->quantity_produced) * 100, 2) : 0,
        ];
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

    public function getBatchSummary(): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'product_name' => $this->product->name,
            'branch_name' => $this->branch->name,
            'location' => $this->warehouseLocation?->location_code,
            'status' => $this->status,
            'quality_status' => $this->quality_status,
            'stock_summary' => $this->getStockSummary(),
            'financial_summary' => [
                'unit_cost' => $this->unit_cost,
                'unit_price' => $this->unit_price,
                'total_value' => $this->getTotalValue(),
                'total_cost' => $this->getTotalCost(),
                'profit' => $this->getProfit(),
                'profit_margin' => $this->getProfitMargin(),
            ],
            'dates' => [
                'production_date' => $this->production_date->format('Y-m-d'),
                'expiry_date' => $this->expiry_date?->format('Y-m-d'),
                'best_before_date' => $this->best_before_date?->format('Y-m-d'),
                'days_to_expiry' => $this->getDaysToExpiry(),
                'days_to_best_before' => $this->getDaysToBestBefore(),
            ],
            'supplier_info' => [
                'supplier_name' => $this->supplier_name,
                'supplier_batch_number' => $this->supplier_batch_number,
            ],
            'quality_info' => [
                'specifications' => $this->specifications,
                'test_results' => $this->quality_test_results,
            ],
        ];
    }
}
