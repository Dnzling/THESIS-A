<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class HomepageFurnitureItem extends Model
{
    protected $fillable = ['slot', 'label', 'description', 'thumbnail_path', 'model_path', 'model_format', 'is_active'];

    protected $casts = [
        'slot' => 'integer',
        'is_active' => 'boolean',
    ];
}
