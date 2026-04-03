<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'type'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'permission_id' => 'integer'
    ];

    /**
     * Validation rule for type
     */
    const TYPE_GRANT = 'grant';
    const TYPE_REVOKE = 'revoke';

    /**
     * Get the user that owns the permission override
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Scope: Only grants
     */
    public function scopeGrants($query)
    {
        return $query->where('type', self::TYPE_GRANT);
    }

    /**
     * Scope: Only revokes
     */
    public function scopeRevokes($query)
    {
        return $query->where('type', self::TYPE_REVOKE);
    }
}