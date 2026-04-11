<?php

namespace App\Models\Ecommerce;

use App\Models\Core\User;
use App\Models\ProductCatalog\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceFavorite extends Model
{
    protected $table = 'ecommerce_favorites';

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

