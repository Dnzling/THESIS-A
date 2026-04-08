<?php

namespace App\Models\Core;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'display_name',
        'code',
        'description',
        'is_active',
    ];
    
    protected $casts = ['is_active' => 'boolean'];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store\Store::class);
    }
}
