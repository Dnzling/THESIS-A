<?php

namespace App\Models\Inventory;

use App\Models\ProductCatalog\Product;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supply extends Product
{
    use SoftDeletes;

    protected $table = 'products';

    protected static function booted(): void
    {
        static::addGlobalScope('supplyType', function ($query) {
            $query->where('product_type', 'supply');
        });
    }
}
