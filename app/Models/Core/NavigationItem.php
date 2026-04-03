<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NavigationItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'route_name',
        'route_path',
        'icon',
        'parent_id',
        'display_order',
        'is_active',
        'meta',
        'section',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array'
    ];

    public function parent()
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('display_order');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'navigation_permissions');
    }
}