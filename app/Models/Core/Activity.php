<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'description', 
        'ip_address', 'user_agent', 'data'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}