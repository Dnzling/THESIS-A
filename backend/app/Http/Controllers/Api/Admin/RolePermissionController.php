<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Core\Role;
use App\Models\Core\Permission;
use App\Models\Core\NavigationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    /**
     * Get all roles with permission counts
     */
    public function getRoles()
    {
        $roles = DB::table('roles')
            ->select('roles.*')
            ->selectRaw('(SELECT COUNT(*) FROM role_permissions WHERE role_id = roles.id) as permissions_count')
            ->selectRaw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id) as users_count')
            ->get();

        return response()->json($roles);
    }

    /**
     * Get all permissions
     */
    public function getPermissions()
    {
        $permissions = DB::table('permissions')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        return response()->json($permissions);
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions($roleId)
    {
        $permissions = DB::table('permissions')
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->select('permissions.*')
            ->get();

        return response()->json(['permissions' => $permissions]);
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions(Request $request, $roleId)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // Delete existing permissions
        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        // Insert new permissions
        $data = collect($request->permissions)->map(function ($permissionId) use ($roleId) {
            return [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        });

        DB::table('role_permissions')->insert($data->toArray());

        return response()->json(['message' => 'Permissions updated successfully']);
    }

    /**
     * Create permission
     */
    public function createPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name|max:100',
            'display_name' => 'required|string|max:200',
            'module' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permission = DB::table('permissions')->insertGetId([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Permission created successfully', 'id' => $permission], 201);
    }

    /**
     * Update permission
     */
    public function updatePermission(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:permissions,name,' . $id,
            'display_name' => 'required|string|max:200',
            'module' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::table('permissions')->where('id', $id)->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Permission updated successfully']);
    }

    /**
     * Delete permission
     */
    public function deletePermission($id)
    {
        DB::table('permissions')->where('id', $id)->update([
            'deleted_at' => now()
        ]);

        return response()->json(['message' => 'Permission deleted successfully']);
    }

    /**
     * Get all navigation items
     */
    public function getNavigationItems()
    {
        $items = DB::table('navigation_items')
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('display_order')
            ->get()
            ->map(function ($item) {
                // Get linked permissions
                $permissions = DB::table('permissions')
                    ->join('navigation_permissions', 'permissions.id', '=', 'navigation_permissions.permission_id')
                    ->where('navigation_permissions.navigation_item_id', $item->id)
                    ->select('permissions.*')
                    ->get();

                $item->permissions = $permissions;
                return $item;
            });

        return response()->json($items);
    }

    /**
     * Create navigation item
     */
    public function createNavigationItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:navigation_items,name|max:100',
            'display_name' => 'required|string|max:200',
            'module' => 'required|string|max:50',
            'route_name' => 'required|string|max:200',
            'route_path' => 'required|string|max:200',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'display_order' => 'integer',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $navId = DB::table('navigation_items')->insertGetId([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'route_name' => $request->route_name,
            'route_path' => $request->route_path,
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'display_order' => $request->display_order ?? 0,
            'is_active' => $request->is_active ?? true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Link permissions
        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissionData = collect($request->permissions)->map(function ($permissionId) use ($navId) {
                return [
                    'navigation_item_id' => $navId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            });

            DB::table('navigation_permissions')->insert($permissionData->toArray());
        }

        return response()->json(['message' => 'Navigation created successfully', 'id' => $navId], 201);
    }

    /**
     * Update navigation item
     */
    public function updateNavigationItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:navigation_items,name,' . $id,
            'display_name' => 'required|string|max:200',
            'module' => 'required|string|max:50',
            'route_name' => 'required|string|max:200',
            'route_path' => 'required|string|max:200',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'display_order' => 'integer',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::table('navigation_items')->where('id', $id)->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'route_name' => $request->route_name,
            'route_path' => $request->route_path,
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'display_order' => $request->display_order ?? 0,
            'is_active' => $request->is_active ?? true,
            'updated_at' => now()
        ]);

        // Update permissions
        DB::table('navigation_permissions')->where('navigation_item_id', $id)->delete();

        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissionData = collect($request->permissions)->map(function ($permissionId) use ($id) {
                return [
                    'navigation_item_id' => $id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            });

            DB::table('navigation_permissions')->insert($permissionData->toArray());
        }

        return response()->json(['message' => 'Navigation updated successfully']);
    }

    /**
     * Delete navigation item
     */
    public function deleteNavigationItem($id)
    {
        DB::table('navigation_items')->where('id', $id)->update([
            'deleted_at' => now()
        ]);

        return response()->json(['message' => 'Navigation deleted successfully']);
    }

    /**
     * Export roles as CSV.
     */
    public function exportRoles()
    {
        $user = Auth::user();
        $storeId = $user?->store_id;

        $roles = DB::table('roles')
            ->when($storeId, function ($query) use ($storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->whereNull('store_id')->orWhere('store_id', $storeId);
                });
            })
            ->orderBy('name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="roles.csv"',
        ];

        $columns = ['id', 'name', 'display_name', 'code', 'description', 'is_active', 'store_id'];

        $callback = function () use ($roles, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($roles as $role) {
                fputcsv($file, [
                    $role->id,
                    $role->name,
                    $role->display_name,
                    $role->code,
                    $role->description,
                    (int) $role->is_active,
                    $role->store_id
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import roles from CSV.
     */
    public function importRoles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $user?->store_id;
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $mapped = array_map(fn($h) => Str::lower(trim($h)), $header);

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($mapped, $row);
            if (!$data || empty($data['name'])) {
                continue;
            }

            $roleStoreId = $data['store_id'] !== '' ? (int) $data['store_id'] : $storeId;

            DB::table('roles')->updateOrInsert(
                ['name' => $data['name'], 'store_id' => $roleStoreId],
                [
                    'display_name' => $data['display_name'] ?? $data['name'],
                    'code' => $data['code'] ?? null,
                    'description' => $data['description'] ?? null,
                    'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        fclose($handle);

        return response()->json(['message' => "Imported {$count} roles successfully"]);
    }

    /**
     * Export permissions as CSV.
     */
    public function exportPermissions()
    {
        $permissions = DB::table('permissions')
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="permissions.csv"',
        ];

        $columns = ['id', 'name', 'display_name', 'module', 'description', 'is_active'];

        $callback = function () use ($permissions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($permissions as $permission) {
                fputcsv($file, [
                    $permission->id,
                    $permission->name,
                    $permission->display_name,
                    $permission->module,
                    $permission->description,
                    (int) $permission->is_active,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import permissions from CSV.
     */
    public function importPermissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $mapped = array_map(fn($h) => Str::lower(trim($h)), $header);

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($mapped, $row);
            if (!$data || empty($data['name']) || empty($data['module'])) {
                continue;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $data['name']],
                [
                    'display_name' => $data['display_name'] ?? $data['name'],
                    'module' => $data['module'],
                    'description' => $data['description'] ?? null,
                    'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        fclose($handle);

        return response()->json(['message' => "Imported {$count} permissions successfully"]);
    }
}
