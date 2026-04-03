<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id'
    ];

    protected $casts = [
        'role_id' => 'integer',
        'permission_id' => 'integer'
    ];

    /**
     * Get the role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}