<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Store\Store;
use App\Services\Core\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function resolveStoreId(Request $request): ?int
    {
        $user = Auth::user();
        $storeId = $user?->store_id ?: $user?->employee?->store_id;

        if (empty($storeId)) {
            $fallbackStoreId = $request->input('user_store_id');
            $storeId = is_numeric($fallbackStoreId) ? (int) $fallbackStoreId : null;
        }

        return !empty($storeId) ? (int) $storeId : null;
    }

    private function getEnabledModulesForStore(?int $storeId): array
    {
        if (empty($storeId)) {
            return [];
        }

        /** @var \App\Services\Modules\ModuleAccessService $modules */
        $modules = app(\App\Services\Modules\ModuleAccessService::class);
        return $modules->enabledModuleKeysForStore($storeId);
    }

    private function getEffectiveEnabledModules(int $storeId): array
    {
        return $this->getEnabledModulesForStore($storeId);
    }

    public function getModules(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        $enabledModules = empty($storeId) ? [] : $this->getEffectiveEnabledModules($storeId);

        $availableModules = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->toArray();

        return response()->json([
            'data' => [
                'enabled_modules' => $enabledModules,
                'available_modules' => $availableModules,
            ]
        ]);
    }

    public function updateModules(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        $userId = Auth::id();

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before updating modules.',
            ], 422);
        }

        $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'required|string|max:50',
        ]);

        $availableModules = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('module')
            ->distinct()
            ->pluck('module')
            ->toArray();

        $validModules = array_values(array_intersect(
            array_unique($request->modules),
            $availableModules
        ));

        // Persist to store_modules as manual source
        foreach ($validModules as $moduleKey) {
            $moduleId = DB::table('modules')->where('key', $moduleKey)->value('id');
            if (!$moduleId) {
                continue;
            }
            DB::table('store_modules')->updateOrInsert(
                ['store_id' => $storeId, 'module_id' => $moduleId],
                [
                    'status' => 'enabled',
                    'source' => 'manual',
                    'enabled_at' => now(),
                    'enabled_by' => $userId,
                ]
            );
        }

        return response()->json([
            'message' => 'Enabled modules updated successfully',
            'data' => [
                'enabled_modules' => $validModules,
            ]
        ]);
    }

    public function index(): JsonResponse
    {
        $storeId = Auth::user()->store_id;
        $globalAllowed = ['store_admin', 'driver'];

        $roles = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id AND users.store_id = ?) as users_count', [$storeId])
            ->selectRaw('(SELECT COUNT(*) FROM employees WHERE role_id = roles.id AND employees.store_id = ?) as employees_count', [$storeId])
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->orWhere(function ($q) use ($globalAllowed) {
                $q->whereNull('store_id')->whereIn('name', $globalAllowed);
            })
            ->orderByRaw('store_id is null desc')
            ->orderBy('display_name')
            ->get();

        return response()->json(['data' => $roles]);
    }

    public function storeSpecific(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        $globalAllowed = ['store_admin', 'driver'];

        if (empty($storeId)) {
            return response()->json(['data' => [], 'store_id' => null]);
        }

        $roles = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) as display_name')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id AND users.store_id = ?) as users_count', [$storeId])
            ->selectRaw('(SELECT COUNT(*) FROM employees WHERE role_id = roles.id AND employees.store_id = ?) as employees_count', [$storeId])
            ->where(function ($query) use ($storeId, $globalAllowed) {
                $query->where('store_id', $storeId)
                    ->orWhere(function ($query) use ($globalAllowed) {
                        $query->whereNull('store_id')->whereIn('name', $globalAllowed);
                    });
            })
            ->orderByRaw('COALESCE(NULLIF(roles.display_name, ""), roles.name) ASC')
            ->get();

        return response()->json(['data' => $roles, 'store_id' => $storeId]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before creating roles.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles')->where(fn($q) => $q->where('store_id', $storeId)),
            ],
            'display_name' => 'required|string',
            'code' => [
                'nullable',
                'string',
                Rule::unique('roles')->where(fn($q) => $q->where('store_id', $storeId)),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $code = $validated['code'] ?? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']));

        $role = Role::create([
            'store_id' => $storeId,
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before updating roles.',
            ], 422);
        }

        $role = Role::where('store_id', $storeId)->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles')->where(fn($q) => $q->where('store_id', $storeId))->ignore($role->id),
            ],
            'display_name' => 'required|string',
            'code' => [
                'nullable',
                'string',
                Rule::unique('roles')->where(fn($q) => $q->where('store_id', $storeId))->ignore($role->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $request = request();
        $storeId = $this->resolveStoreId($request);

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before deleting roles.',
            ], 422);
        }

        $role = Role::where('store_id', $storeId)->findOrFail($id);

        $userCount = DB::table('users')->where('role_id', $role->id)->where('store_id', $storeId)->count();
        $employeeCount = DB::table('employees')->where('role_id', $role->id)->where('store_id', $storeId)->count();
        if ($userCount > 0 || $employeeCount > 0) {
            return response()->json([
                'message' => 'Cannot delete a role with assigned employees or users. Reassign them first.',
            ], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getPermissions(Request $request): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);
        $enabledModules = empty($storeId) ? [] : $this->getEffectiveEnabledModules($storeId);

        $permissionsQuery = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('name');

        // Match admin behavior when no module filter is configured: show all active permissions.
        if (!empty($enabledModules)) {
            $permissionsQuery->whereIn('module', $enabledModules);
        }

        $permissions = $permissionsQuery->get();

        return response()->json(['data' => $permissions]);
    }

    public function getRolePermissions(int $roleId): JsonResponse
    {
        $request = request();
        $storeId = $this->resolveStoreId($request);

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before managing role permissions.',
            ], 422);
        }

        $globalAllowed = ['store_admin', 'driver'];
        $role = Role::where(function ($q) use ($storeId) {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
            })
            ->where(function ($q) use ($storeId, $globalAllowed) {
                $q->where('store_id', $storeId)
                  ->orWhere(function ($inner) use ($globalAllowed) {
                      $inner->whereNull('store_id')->whereIn('name', $globalAllowed);
                  });
            })
            ->findOrFail($roleId);

        $permissions = DB::table('permissions')
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $role->id)
            ->select('permissions.*')
            ->get();

        return response()->json(['permissions' => $permissions, 'role' => $role]);
    }

    public function updateRolePermissions(Request $request, int $roleId): JsonResponse
    {
        $storeId = $this->resolveStoreId($request);

        if (empty($storeId)) {
            return response()->json([
                'message' => 'You must be assigned to a store before updating role permissions.',
            ], 422);
        }

        $globalAllowed = ['store_admin', 'driver'];
        $role = Role::query()
            ->where(function ($query) use ($storeId, $globalAllowed) {
                $query->where('store_id', $storeId)
                    ->orWhere(function ($query) use ($globalAllowed) {
                        $query->whereNull('store_id')->whereIn('name', $globalAllowed);
                    });
            })
            ->findOrFail($roleId);
        $enabledModules = $this->getEffectiveEnabledModules($storeId);

        $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $invalidPermissionIds = [];
        if (!empty($enabledModules)) {
            $invalidPermissionIds = DB::table('permissions')
                ->whereIn('id', $request->permissions)
                ->whereNotIn('module', $enabledModules)
                ->pluck('id')
                ->toArray();
        }

        if (!empty($invalidPermissionIds)) {
            return response()->json([
                'message' => 'Some permissions belong to modules not enabled for your store.',
                'invalid_permission_ids' => $invalidPermissionIds,
            ], 422);
        }

        $permissionIds = collect($request->permissions)
            ->map(fn ($permissionId) => (int) $permissionId)
            ->unique()
            ->values();

        DB::transaction(function () use ($role, $permissionIds) {
            DB::table('role_permissions')->where('role_id', $role->id)->delete();

            if ($permissionIds->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('role_permissions')->insert($permissionIds->map(fn ($permissionId) => [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        $permissionService = app(PermissionService::class);
        User::query()
            ->where('role_id', $role->id)
            ->select(['id', 'store_id'])
            ->chunkById(100, function ($users) use ($permissionService): void {
                foreach ($users as $user) {
                    $permissionService->clearUserCache($user);
                }
            });

        return response()->json([
            'message' => 'Permissions updated successfully',
            'permissions' => DB::table('permissions')
                ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role_id', $role->id)
                ->select('permissions.*')
                ->get(),
        ]);
    }
}
