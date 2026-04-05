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

        $link = $payload['link'] ?? null;

        if (empty($link) && !empty($payload['entity_type']) && !empty($payload['entity_id'])) {
            switch ($payload['entity_type']) {
                case 'request_for_quotation':
                case 'rfq':
                    $link = "/system/procurement/rfqs/{$payload['entity_id']}";
                    break;
                case 'purchase_order':
                case 'po':
                    $link = "/system/procurement/purchase-orders/{$payload['entity_id']}";
                    break;
                case 'purchase_requisition':
                case 'pr':
                    $link = "/system/procurement/purchase-requisitions/{$payload['entity_id']}";
                    break;
                default:
                    $link = null;
            }
        }

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
            'link' => $link,
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

    protected function userIdsWithAnyPermission(int $storeId, array $permissions): array
    {
        $permissionNames = collect($permissions)
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->map(fn($value) => trim((string) $value))
            ->unique()
            ->values();

        if ($permissionNames->isEmpty()) {
            return [];
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames->all())
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($permissionIds)) {
            return [];
        }

        $roleBasedUserIds = DB::table('users')
            ->join('role_permissions', 'users.role_id', '=', 'role_permissions.role_id')
            ->where('users.store_id', $storeId)
            ->whereIn('role_permissions.permission_id', $permissionIds)
            ->pluck('users.id')
            ->map(fn($id) => (int) $id)
            ->all();

        $grantedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('user_permissions.type', 'grant')
            ->whereIn('user_permissions.permission_id', $permissionIds)
            ->pluck('users.id')
            ->map(fn($id) => (int) $id)
            ->all();

        $revokedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('user_permissions.type', 'revoke')
            ->whereIn('user_permissions.permission_id', $permissionIds)
            ->pluck('users.id')
            ->map(fn($id) => (int) $id)
            ->all();

        $candidateIds = collect(array_merge($roleBasedUserIds, $grantedUserIds))
            ->unique()
            ->reject(fn($id) => in_array((int) $id, $revokedUserIds, true))
            ->values()
            ->all();

        return array_map('intval', $candidateIds);
    }

    protected function notifyUsersByPermissions(
        int $storeId,
        array $permissions,
        array $payload,
        array $excludeUserIds = []
    ): array {
        $exclude = collect($excludeUserIds)->map(fn($id) => (int) $id)->all();

        $userIds = collect($this->userIdsWithAnyPermission($storeId, $permissions))
            ->reject(fn($id) => in_array((int) $id, $exclude, true))
            ->values()
            ->all();

        if (!empty($userIds)) {
            $this->notifyMany($userIds, $payload);
        }

        return $userIds;
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
