<?php
// backend/app/Models/Inventory/SerialNumber.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use Carbon\Carbon;

class SerialNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'serial_number',
        'product_id',
        'branch_id',
        'warehouse_location_id',
        'status',
        'condition',
        'purchase_price',
        'selling_price',
        'purchase_date',
        'sold_date',
        'warranty_expiry',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchase_date' => 'date',
        'sold_date' => 'date',
        'warranty_expiry' => 'date',
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
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopeDamaged($query)
    {
        return $query->where('status', 'damaged');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeWarrantyExpired($query)
    {
        return $query->where('warranty_expiry', '<', now());
    }

    public function scopeWarrantyActive($query)
    {
        return $query->where('warranty_expiry', '>', now());
    }

    public function scopeInLocation($query, $locationId)
    {
        return $query->where('warehouse_location_id', $locationId);
    }

    // Helper methods
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isDamaged(): bool
    {
        return $this->status === 'damaged';
    }

    public function isWarrantyActive(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry->isFuture();
    }

    public function isWarrantyExpired(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry->isPast();
    }

    public function canBeSold(): bool
    {
        return in_array($this->status, ['available', 'reserved']) && !$this->isDamaged();
    }

    public function canBeReserved(): bool
    {
        return $this->status === 'available';
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'sold';
    }

    public function sell(?float $sellingPrice = null, ?string $notes = null): bool
    {
        if (!$this->canBeSold()) {
            return false;
        }

        $this->update([
            'status' => 'sold',
            'selling_price' => $sellingPrice ?? $this->selling_price,
            'sold_date' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function reserve(?string $notes = null): bool
    {
        if (!$this->canBeReserved()) {
            return false;
        }

        $this->update([
            'status' => 'reserved',
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function unreserve(?string $notes = null): bool
    {
        if ($this->status !== 'reserved') {
            return false;
        }

        $this->update([
            'status' => 'available',
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function markAsDamaged(?string $notes = null): bool
    {
        if ($this->status === 'sold') {
            return false; // Can't mark sold items as damaged
        }

        $this->update([
            'status' => 'damaged',
            'condition' => 'damaged',
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function return(?string $notes = null): bool
    {
        if (!$this->canBeReturned()) {
            return false;
        }

        $this->update([
            'status' => 'returned',
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function moveToLocation(?int $locationId, ?string $notes = null): bool
    {
        $this->update([
            'warehouse_location_id' => $locationId,
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);

        return true;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'available' => 'green',
            'sold' => 'blue',
            'reserved' => 'orange',
            'damaged' => 'red',
            'returned' => 'purple',
            'in_transit' => 'yellow',
            default => 'gray',
        };
    }

    public function getConditionColor(): string
    {
        return match($this->condition) {
            'new' => 'green',
            'used' => 'blue',
            'refurbished' => 'orange',
            'damaged' => 'red',
            default => 'gray',
        };
    }

    public function getDaysSincePurchase(): ?int
    {
        return $this->purchase_date ? $this->purchase_date->diffInDays(now()) : null;
    }

    public function getDaysSinceSold(): ?int
    {
        return $this->sold_date ? $this->sold_date->diffInDays(now()) : null;
    }

    public function getDaysUntilWarrantyExpiry(): ?int
    {
        if (!$this->warranty_expiry) {
            return null;
        }

        return now()->diffInDays($this->warranty_expiry, false);
    }

    public function calculateProfit(): ?float
    {
        if (!$this->purchase_price || !$this->selling_price) {
            return null;
        }

        return $this->selling_price - $this->purchase_price;
    }

    public function getProfitMargin(): ?float
    {
        if (!$this->purchase_price || !$this->selling_price || $this->purchase_price == 0) {
            return null;
        }

        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
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

    public function getSerialSummary(): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'product_name' => $this->product->name,
            'branch_name' => $this->branch->name,
            'location' => $this->warehouseLocation?->location_code,
            'status' => $this->status,
            'condition' => $this->condition,
            'purchase_price' => $this->purchase_price,
            'selling_price' => $this->selling_price,
            'profit' => $this->calculateProfit(),
            'profit_margin' => $this->getProfitMargin(),
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'sold_date' => $this->sold_date?->format('Y-m-d'),
            'warranty_expiry' => $this->warranty_expiry?->format('Y-m-d'),
            'warranty_active' => $this->isWarrantyActive(),
            'days_since_purchase' => $this->getDaysSincePurchase(),
            'days_since_sold' => $this->getDaysSinceSold(),
            'days_until_warranty_expiry' => $this->getDaysUntilWarrantyExpiry(),
        ];
    }
}
