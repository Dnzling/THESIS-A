<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Core\SystemNotification;

abstract class Controller
{
    protected function notify(int $userId, array $payload): SystemNotification
    {
        $user = auth()->user();
        $storeId = $payload['store_id'] ?? $user?->store_id;
        $branchId = $payload['branch_id'] ?? $user?->branch_id;

        return SystemNotification::create([
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'module' => $payload['module'] ?? 'general',
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id' => $payload['entity_id'] ?? null,
            'action' => $payload['action'] ?? null,
            'title' => $payload['title'] ?? 'Notification',
            'message' => $payload['message'] ?? null,
            'data' => $payload['data'] ?? null,
            'link' => $payload['link'] ?? null,
            'severity' => $payload['severity'] ?? 'info',
            'is_read' => (bool) ($payload['is_read'] ?? false),
            'read_at' => $payload['read_at'] ?? null,
        ]);
    }

    protected function notifyMany(array $userIds, array $payload): void
    {
        foreach ($userIds as $userId) {
            $this->notify((int) $userId, $payload);
        }
    }
    protected function getUserPermissions($user = null): array
    {
        $user = $user ?? auth()->user();

        if (!$user || !$user->role_id) {
            return [];
        }

        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $user->role_id)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        $userGrants = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'grant')
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->pluck('permissions.name')
            ->toArray();

        $userRevokes = DB::table('user_permissions')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->where('user_permissions.user_id', $user->id)
            ->where('user_permissions.type', 'revoke')
            ->pluck('permissions.name')
            ->toArray();

        $allPermissions = array_merge($rolePermissions, $userGrants);
        $finalPermissions = array_diff($allPermissions, $userRevokes);

        return array_values(array_unique($finalPermissions));
    }

    protected function userHasPermissions(array $permissions, $user = null): bool
    {
        if (empty($permissions)) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        return empty(array_diff($permissions, $userPermissions));
    }

    protected function userHasAnyPermission(array $permissions, $user = null): bool
    {
        if (empty($permissions)) {
            return false;
        }

        $userPermissions = $this->getUserPermissions($user);

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                return true;
            }
        }

        return false;
    }
}
