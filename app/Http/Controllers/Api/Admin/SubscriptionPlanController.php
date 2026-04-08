<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function show(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $modules = DB::table('modules')
            ->leftJoin('plan_modules', function ($join) use ($subscriptionPlan) {
                $join->on('plan_modules.module_id', '=', 'modules.id')
                    ->where('plan_modules.plan_id', '=', $subscriptionPlan->id);
            })
            ->select(
                'modules.id',
                'modules.key',
                'modules.name',
                'modules.description',
                'modules.is_active',
                DB::raw('COALESCE(plan_modules.included, 0) as included')
            )
            ->orderBy('modules.name')
            ->get()
            ->map(function ($m) {
                $m->included = (bool) $m->included;
                return $m;
            });

        $permissions = DB::table('permissions')
            ->leftJoin('plan_permissions', function ($join) use ($subscriptionPlan) {
                $join->on('plan_permissions.permission_id', '=', 'permissions.id')
                    ->where('plan_permissions.plan_id', '=', $subscriptionPlan->id);
            })
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.module',
                'permissions.display_name',
                'permissions.description',
                DB::raw('COALESCE(plan_permissions.included, 0) as included')
            )
            ->where('permissions.is_active', true)
            ->orderBy('permissions.module')
            ->orderBy('permissions.display_name')
            ->get()
            ->map(function ($p) {
                $p->included = (bool) $p->included;
                return $p;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $subscriptionPlan,
                'modules' => $modules,
                'permissions' => $permissions,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'plan_key' => 'required|string|max:50|alpha_dash|unique:subscription_plans,plan_key',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:200',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $plan = SubscriptionPlan::create([
            'plan_key' => strtolower($validated['plan_key']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
            'features' => $validated['features'] ?? [],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => $plan,
        ], 201);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:200',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'modules' => 'nullable|array',
            'modules.*' => 'string|exists:modules,key',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $subscriptionPlan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
            'features' => $validated['features'] ?? [],
            'is_featured' => (bool) ($validated['is_featured'] ?? $subscriptionPlan->is_featured),
            'is_active' => (bool) ($validated['is_active'] ?? $subscriptionPlan->is_active),
            'sort_order' => (int) ($validated['sort_order'] ?? $subscriptionPlan->sort_order),
        ]);

        if (isset($validated['modules'])) {
            $moduleIds = DB::table('modules')->whereIn('key', $validated['modules'])->pluck('id')->toArray();
            // Upsert selected modules as included
            foreach ($moduleIds as $mid) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $subscriptionPlan->id, 'module_id' => $mid],
                    ['included' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
            // Set non-selected modules to excluded
            $allModuleIds = DB::table('modules')->pluck('id')->toArray();
            $toDisable = array_diff($allModuleIds, $moduleIds);
            if (!empty($toDisable)) {
                DB::table('plan_modules')
                    ->where('plan_id', $subscriptionPlan->id)
                    ->whereIn('module_id', $toDisable)
                    ->update(['included' => false, 'updated_at' => now()]);
            }
        }

        if (isset($validated['permissions'])) {
            $permIds = DB::table('permissions')->pluck('id', 'name')->toArray();
            $selectedIds = [];
            foreach ($validated['permissions'] as $permName) {
                if (isset($permIds[$permName])) {
                    $selectedIds[] = $permIds[$permName];
                }
            }

            $allIds = array_values($permIds);

            // Include selected
            foreach ($selectedIds as $pid) {
                DB::table('plan_permissions')->updateOrInsert(
                    ['plan_id' => $subscriptionPlan->id, 'permission_id' => $pid],
                    ['included' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
            // Exclude unselected
            $toExclude = array_diff($allIds, $selectedIds);
            if (!empty($toExclude)) {
                DB::table('plan_permissions')
                    ->where('plan_id', $subscriptionPlan->id)
                    ->whereIn('permission_id', $toExclude)
                    ->update(['included' => false, 'updated_at' => now()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully.',
            'data' => $subscriptionPlan->fresh(),
        ]);
    }

    public function publicIndex(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }
}
