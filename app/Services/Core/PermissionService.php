<?php

namespace App\Services\Core;

use App\Models\Core\Permission;
use App\Models\Core\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Check if user has a specific permission atom.
     */
    public function userHasPermission(User $user, string $permissionName, ?int $storeId = null): bool
    {
        $permissions = $this->getUserPermissions($user, $storeId);

        if (in_array($permissionName, $permissions, true)) {
            return true;
        }

        // Treat ".manage" as an implicit superset of ".view" for the same resource.
        if (str_ends_with($permissionName, '.view')) {
            $managePermission = preg_replace('/\.view$/', '.manage', $permissionName);
            if ($managePermission && in_array($managePermission, $permissions, true)) {
                return true;
            }
        }

        // Allow wildcard fallback: module.*.action, module.resource.*
        $segments = explode('.', $permissionName);

        if (count($segments) >= 3) {
            $wildcards = [
                $segments[0] . '.*.' . end($segments),
                $segments[0] . '.' . $segments[1] . '.*',
                $segments[0] . '.*.*',
            ];

            foreach ($wildcards as $wildcard) {
                if (in_array($wildcard, $permissions, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get flattened permission names for user with role + overrides.
     */
    public function getUserPermissions(User $user, ?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? ($user->store_id ? (int) $user->store_id : null);
        $cacheKey = $this->getCacheKey($user->id, $resolvedStoreId);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $resolvedStoreId): array {
            if ($resolvedStoreId !== null && !$this->belongsToStore($user, $resolvedStoreId)) {
                return [];
            }

            $rolePermissionNames = Permission::query()
                ->select('permissions.name')
                ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('role_permissions.role_id', $user->role_id)
                ->where('permissions.is_active', true)
                ->pluck('permissions.name')
                ->all();

            $grantOverrides = $user->userPermissions()
                ->with('permission:id,name')
                ->where('type', 'grant')
                ->get()
                ->pluck('permission.name')
                ->filter()
                ->values()
                ->all();

            $revokeOverrides = $user->userPermissions()
                ->with('permission:id,name')
                ->where('type', 'revoke')
                ->get()
                ->pluck('permission.name')
                ->filter()
                ->values()
                ->all();

            $permissionNames = array_values(array_unique(array_merge($rolePermissionNames, $grantOverrides)));

            if (!empty($revokeOverrides)) {
                $permissionNames = array_values(array_diff($permissionNames, $revokeOverrides));
            }

            if ($resolvedStoreId !== null) {
                $planPermissions = DB::table('stores')
                    ->join('plan_permissions', 'plan_permissions.plan_id', '=', 'stores.subscription_tier')
                    ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
                    ->where('stores.id', $resolvedStoreId)
                    ->where('plan_permissions.included', true)
                    ->where('permissions.is_active', true)
                    ->whereNull('permissions.deleted_at')
                    ->pluck('permissions.name')
                    ->all();

                $permissionNames = array_values(array_intersect($permissionNames, $planPermissions));
            }

            return $permissionNames;
        });
    }

    /**
     * Clear permission cache for all contexts of a user.
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget($this->getCacheKey($user->id, null));

        if ($user->store_id) {
            Cache::forget($this->getCacheKey($user->id, (int) $user->store_id));
        }
    }

    public function clearStoreCache(int $storeId): void
    {
        User::query()
            ->where('store_id', $storeId)
            ->pluck('id')
            ->each(function ($userId) use ($storeId): void {
                Cache::forget($this->getCacheKey((int) $userId, $storeId));
                Cache::forget($this->getCacheKey((int) $userId, null));
            });
    }

    protected function belongsToStore(User $user, int $storeId): bool
    {
        if ((int) $user->store_id === $storeId) {
            return true;
        }

        return $user->stores()->where('stores.id', $storeId)->exists();
    }

    protected function getCacheKey(int $userId, ?int $storeId): string
    {
        return sprintf('permissions:user:%d:store:%s', $userId, $storeId ?? 'global');
    }
}
