<?php
// backend/app/Models/Inventory/StockAlert.php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store\Branch;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use App\Models\Hr\Employee;

class StockAlert extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'variation_id',
        'alert_type',
        'current_quantity',
        'reorder_point',
        'recommended_order_quantity',
        'status',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'reorder_point' => 'integer',
        'recommended_order_quantity' => 'integer',
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->where('alert_type', 'low_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('alert_type', 'out_of_stock');
    }

    // Helper Methods
    public function acknowledge(int $employeeId): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $employeeId,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved']);
    }
}