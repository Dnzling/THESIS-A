<?php

namespace App\Services\Logistics;

use App\Models\Core\User;
use App\Services\Core\PermissionService;

class DriverPermissionEligibility
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function userCanDrive(User $user, ?int $storeId = null): bool
    {
        foreach ($this->permissions->getUserPermissions($user, $storeId) as $permission) {
            if (str_starts_with((string) $permission, 'logistics.driver.')) {
                return true;
            }
        }

        return false;
    }
}
