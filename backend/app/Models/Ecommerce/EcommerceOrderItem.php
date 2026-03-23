<?php

namespace App\Models\Ecommerce;

use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EcommerceOrderItem extends Model
{
    protected $table = 'ecommerce_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'branch_inventory_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branchInventory(): BelongsTo
    {
        return $this->belongsTo(BranchInventory::class, 'branch_inventory_id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(EcommerceOrderReturn::class, 'order_item_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(EcommerceProductReview::class, 'order_item_id');
    }
}
