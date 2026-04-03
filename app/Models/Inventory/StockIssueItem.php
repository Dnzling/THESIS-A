<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIssueItem extends Model
{
    protected $fillable = [
        'stock_issue_id',
        'inventory_item_id',
        'quantity',
        'unit_cost',
        'total_value',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    /**
     * Get the stock issue this item belongs to
     */
    public function stockIssue(): BelongsTo
    {
        return $this->belongsTo(StockIssue::class);
    }

    /**
     * Get the inventory item being issued
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class, 'inventory_item_id');
    }
}
