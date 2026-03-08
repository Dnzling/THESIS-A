<?php

namespace App\Models\Core;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'is_active', 'description'];
    
    protected $casts = ['is_active' => 'boolean'];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}