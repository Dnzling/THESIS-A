<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreModuleController extends Controller
{
    public function __construct(private ModuleAccessService $modules)
    {
    }

    public function stores(): JsonResponse
    {
        $stores = DB::table('stores')
            ->select('id', 'store_name', 'store_code', 'subscription_tier', 'status', 'email', 'phone')
            ->orderBy('store_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $stores, // mimic paginated structure used in other admin endpoints
            ],
        ]);
    }

    public function modules(): JsonResponse
    {
        $modules = DB::table('modules')
            ->select('id', 'key', 'name', 'description', 'is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $storeId = (int) $request->input('store_id');
        $moduleRows = DB::table('modules')
            ->select('modules.id', 'modules.key', 'modules.name', 'modules.description', 'modules.is_active')
            ->orderBy('modules.name')
            ->get();

        $overrides = DB::table('store_module_overrides')
            ->where('store_id', $storeId)
            ->pluck('allow', 'module_id');

        $storeStatuses = DB::table('store_modules')
            ->where('store_id', $storeId)
            ->pluck('status', 'module_id');

        $data = $moduleRows->map(function ($m) use ($storeId, $overrides, $storeStatuses) {
            $override = $overrides[$m->id] ?? null;
            $status = $storeStatuses[$m->id] ?? null;

            return [
                'module_id' => $m->id,
                'module_key' => $m->key,
                'name' => $m->name,
                'description' => $m->description,
                'active' => (bool) $m->is_active,
                'effective_enabled' => $this->modules->isEnabledForStore($storeId, $m->key),
                'store_status' => $status,
                'override' => $override, // true/false/null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'modules' => $data,
            ],
        ]);
    }

    public function override(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'module_key' => 'required|string|exists:modules,key',
            'allow' => 'nullable|boolean',
            'reason' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date',
        ]);

        $storeId = (int) $validated['store_id'];
        $moduleId = (int) DB::table('modules')->where('key', $validated['module_key'])->value('id');
        $oldAllow = DB::table('store_module_overrides')
            ->where('store_id', $storeId)
            ->where('module_id', $moduleId)
            ->value('allow');

        if (!$moduleId) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        if (is_null($validated['allow'])) {
            DB::table('store_module_overrides')
                ->where('store_id', $storeId)
                ->where('module_id', $moduleId)
                ->delete();
        } else {
            DB::table('store_module_overrides')->updateOrInsert(
                ['store_id' => $storeId, 'module_id' => $moduleId],
                [
                    'allow' => (bool) $validated['allow'],
                    'reason' => $validated['reason'] ?? null,
                    'expires_at' => $validated['expires_at'] ?? null,
                    'set_by' => $request->user()?->id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Auto-sync store_admin role permissions for this module so nav/actions appear when enabled
        $this->syncStoreAdminPermissions($storeId, $moduleId, $validated['allow']);

        DB::table('store_module_override_logs')->insert([
            'store_id' => $storeId,
            'module_id' => $moduleId,
            'user_id' => $request->user()?->id,
            'old_allow' => $oldAllow,
            'new_allow' => $validated['allow'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Override updated.',
        ]);
    }

    /**
     * When a module is forced on/off, grant or revoke all permissions of that module
     * to the store_admin role for the store, so navigation and actions align.
     */
    private function syncStoreAdminPermissions(int $storeId, int $moduleId, bool|null $allow): void
    {
        $roleId = DB::table('roles')
            ->where('store_id', $storeId)
            ->where('name', 'store_admin')
            ->value('id');

        if (!$roleId) {
            return;
        }

        $moduleKey = DB::table('modules')->where('id', $moduleId)->value('key');

        $permissionQuery = DB::table('permissions');
        if (Schema::hasColumn('permissions', 'module_id')) {
            $permissionQuery->where('module_id', $moduleId);
        }
        if ($moduleKey) {
            $permissionQuery->orWhere('module', $moduleKey);
        }
        $permissionIds = $permissionQuery->pluck('id')->toArray();

        if (empty($permissionIds)) {
            return;
        }

        if ($allow === true) {
            // Grant missing permissions
            $existing = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->pluck('permission_id')
                ->toArray();

            $missing = array_diff($permissionIds, $existing);
            if (!empty($missing)) {
                $insert = array_map(fn ($pid) => [
                    'role_id' => $roleId,
                    'permission_id' => $pid,
                ], $missing);
                DB::table('role_permissions')->insert($insert);
            }
        } elseif ($allow === false) {
            // Revoke permissions for this module
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
        // If allow is null (reset), leave existing permissions as-is.
    }
}
