<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $storeId = auth()->user()->store_id;

        $roles = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id AND users.store_id = ?) as users_count', [$storeId])
            ->where(function ($q) use ($storeId) {
                $q->whereNull('store_id')
                    ->orWhere('store_id', $storeId);
            })
            ->orderByRaw('store_id is null desc')
            ->orderBy('display_name')
            ->get();

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = auth()->user()->store_id;

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

        $code = $validated['code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 5));

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
        $storeId = auth()->user()->store_id;
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
        $storeId = auth()->user()->store_id;
        $role = Role::where('store_id', $storeId)->findOrFail($id);

        $userCount = DB::table('users')->where('role_id', $role->id)->where('store_id', $storeId)->count();
        if ($userCount > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users',
            ], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getPermissions(): JsonResponse
    {
        $permissions = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $permissions]);
    }

    public function getRolePermissions(int $roleId): JsonResponse
    {
        $storeId = auth()->user()->store_id;
        $role = Role::where(function ($q) use ($storeId) {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
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
        $storeId = auth()->user()->store_id;
        $role = Role::where('store_id', $storeId)->findOrFail($roleId);

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        DB::table('role_permissions')->where('role_id', $role->id)->delete();

        $data = collect($request->permissions)->map(function ($permissionId) use ($role) {
            return [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        });

        DB::table('role_permissions')->insert($data->toArray());

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
