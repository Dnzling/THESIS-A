<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class HomepageModuleSlide extends Model
{
    protected $fillable = ['name', 'description', 'benefits', 'image_path', 'sort_order', 'is_active'];

    protected $casts = [
        'benefits' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
