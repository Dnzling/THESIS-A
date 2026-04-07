<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreModuleController extends Controller
{
    public function __construct(private ModuleAccessService $modules)
    {
    }

    public function stores(): JsonResponse
    {
        $stores = DB::table('stores')
            ->select('id', 'name as store_name', 'name', 'store_code', 'subscription_tier', 'status', 'email', 'phone')
            ->orderBy('name')
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

        return response()->json([
            'success' => true,
            'message' => 'Override updated.',
        ]);
    }
}
