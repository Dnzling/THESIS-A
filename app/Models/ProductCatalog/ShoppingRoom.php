<?php

namespace App\Models\ProductCatalog;

use Illuminate\Database\Eloquent\Model;

class ShoppingRoom extends Model
{
    protected $fillable = ['name', 'slug', 'image_path', 'is_active', 'display_order'];

    protected $casts = ['is_active' => 'boolean', 'display_order' => 'integer'];
}
