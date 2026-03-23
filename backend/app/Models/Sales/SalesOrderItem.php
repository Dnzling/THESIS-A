<?php

namespace App\Models\Sales;

use App\Models\Inventory\BranchInventory;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $table = 'sales_pos_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'branch_inventory_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'line_discount',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
    public function variation(): BelongsTo { return $this->belongsTo(ProductVariation::class, 'variation_id'); }
    public function branchInventory(): BelongsTo { return $this->belongsTo(BranchInventory::class, 'branch_inventory_id'); }
}

