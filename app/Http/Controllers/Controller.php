<?php

namespace App\Http\Controllers;

use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Services\Core\PermissionService;

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

        $creatorName = trim(collect([$user?->fname, $user?->lname])->filter()->implode(' '));
        $notificationData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        if ($user && ! $user->isSuperAdmin()) {
            $notificationData['created_by'] = [
                'id' => $user->id,
                'name' => $creatorName !== '' ? $creatorName : ($user->name ?? "User #{$user->id}"),
                'email' => $user->email,
            ];
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
            'data' => $notificationData,
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

        $permissionService = app(PermissionService::class);

        return User::query()->where('store_id', $storeId)->where('is_active', true)
            ->get(['id', 'store_id', 'role_id'])
            ->filter(fn (User $user) => $permissionNames->contains(
                fn (string $permission) => $permissionService->userHasPermission($user, $permission, $storeId)
            ))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
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

        return app(\App\Services\Core\PermissionService::class)
            ->getUserPermissions($user, $user->store_id ? (int) $user->store_id : null);
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

    protected function userHasAnyPermissionSet(array $permissionSet, $user = null): bool
    {
        if (empty($permissionSet)) {
            return false;
        }

        $normalizedUserPermissions = collect($this->getUserPermissions($user))
            ->map(fn($permission) => $this->normalizePermissionName((string) $permission))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($permissionSet as $permission) {
            $normalizedPermission = $this->normalizePermissionName((string) $permission);
            if ($normalizedPermission !== '' && in_array($normalizedPermission, $normalizedUserPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    protected function userHasAllPermissionSets(array $permissionSets, $user = null): bool
    {
        if (empty($permissionSets)) {
            return true;
        }

        foreach ($permissionSets as $set) {
            if (!$this->userHasAnyPermissionSet((array) $set, $user)) {
                return false;
            }
        }

        return true;
    }

    protected function normalizePermissionName(string $permission): string
    {
        $normalized = strtolower(trim($permission));
        if ($normalized === '') {
            return '';
        }

        return str_replace('-', '_', $normalized);
    }
}
