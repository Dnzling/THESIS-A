<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use App\Models\Hr\Department;
use App\Models\Store\Store;
use App\Services\Store\StoreTrialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PositionSetupController extends Controller
{
    private const SUGGESTIONS = [
        'inventory' => ['Inventory Manager', 'Warehouse Staff', 'Stock Controller'],
        'warehouse' => ['Warehouse Manager', 'Warehouse Staff', 'Stock Controller'],
        'sales' => ['Sales Manager', 'Sales Associate', 'Cashier'],
        'procurement' => ['Procurement Manager', 'Purchasing Staff', 'Supplier Coordinator'],
        'logistics' => ['Delivery Coordinator', 'Driver', 'Dispatcher'],
        'delivery' => ['Delivery Coordinator', 'Driver', 'Dispatcher'],
        'ecommerce' => ['E-Commerce Manager', 'Product Manager', 'Customer Support'],
        'merchandising' => ['Merchandising Manager', 'Product Merchandiser', 'Catalog Staff'],
        'finance' => ['Finance Manager', 'Accountant', 'Bookkeeper'],
        'hr' => ['HR Manager', 'HR Staff', 'Recruiter'],
        'crm' => ['Customer Service Manager', 'Customer Support', 'Customer Relations Staff'],
        'analytics' => ['Analytics Manager', 'Business Analyst', 'Reporting Staff'],
    ];

    public function index(Request $request)
    {
        $store = $this->ownedStore($request);
        $enabledModules = app(\App\Services\Modules\ModuleAccessService::class)->enabledModuleKeysForStore((int) $store->id);
        $modules = DB::table('stores')
            ->join('plan_permissions', 'plan_permissions.plan_id', '=', 'stores.subscription_tier')
            ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
            ->where('stores.id', $store->id)
            ->where('plan_permissions.included', true)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->whereNotNull('permissions.module')
            ->whereIn('permissions.module', $enabledModules)
            ->distinct()
            ->orderBy('permissions.module')
            ->pluck('permissions.module');

        $suggestions = [];
        foreach ($modules as $module) {
            $module = (string) $module;
            $names = self::SUGGESTIONS[$module] ?? [Str::headline($module) . ' Manager', Str::headline($module) . ' Staff', Str::headline($module) . ' Specialist'];
            $permissionNames = DB::table('stores')
                ->join('plan_permissions', 'plan_permissions.plan_id', '=', 'stores.subscription_tier')
                ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
                ->where('stores.id', $store->id)
                ->where('plan_permissions.included', true)
                ->where('permissions.module', $module)
                ->where('permissions.is_active', true)
                ->whereNull('permissions.deleted_at')
                ->pluck('permissions.display_name')
                ->all();

            foreach ($names as $index => $name) {
                $suggestions[] = [
                    'key' => $module . ':' . Str::slug($name),
                    'module' => $module,
                    'name' => $name,
                    'department' => Str::headline($module),
                    'permissions' => $permissionNames,
                    'recommended' => $index === 0 && count(array_filter($suggestions, fn ($item) => $item['recommended'])) < 3,
                ];
            }
        }

        return response()->json([
            'required' => app(StoreTrialService::class)->needsPositionSetup($store),
            'suggestions' => $suggestions,
        ]);
    }

    public function store(Request $request)
    {
        $store = $this->ownedStore($request);
        if (!app(StoreTrialService::class)->needsPositionSetup($store)) {
            return response()->json(['message' => 'Position setup is not required.'], 409);
        }

        $validated = $request->validate(['positions' => 'required|array', 'positions.*' => 'required|string|max:120']);
        $suggestions = collect($this->index($request)->getData(true)['suggestions'])->keyBy('key');
        $selected = collect($validated['positions'])->unique();
        if ($selected->diff($suggestions->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['positions' => 'One or more positions are not included in your current plan.']);
        }

        DB::transaction(function () use ($store, $request, $selected, $suggestions) {
            $locked = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            if (!app(StoreTrialService::class)->needsPositionSetup($locked)) {
                return;
            }

            foreach ($selected as $key) {
                $suggestion = $suggestions->get($key);
                $module = $suggestion['module'];
                $department = Department::firstOrCreate(
                    ['store_id' => $store->id, 'name' => $suggestion['department']],
                    ['description' => 'Department for ' . $module, 'status' => 'active', 'created_by' => $request->user()->id]
                );
                $role = Role::firstOrCreate(
                    ['store_id' => $store->id, 'name' => Str::slug($suggestion['name'], '_')],
                    ['display_name' => $suggestion['name'], 'code' => Str::upper(Str::limit(Str::slug($suggestion['name'], '_'), 40, '')), 'description' => 'Suggested ' . $module . ' position', 'is_active' => true]
                );
                $department->roles()->syncWithoutDetaching([$role->id]);
                $ids = DB::table('stores')
                    ->join('plan_permissions', 'plan_permissions.plan_id', '=', 'stores.subscription_tier')
                    ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
                    ->where('stores.id', $store->id)
                    ->where('plan_permissions.included', true)
                    ->where('permissions.module', $module)
                    ->where('permissions.is_active', true)
                    ->whereNull('permissions.deleted_at')
                    ->pluck('permissions.id');
                foreach ($ids as $id) {
                    DB::table('role_permissions')->updateOrInsert(['role_id' => $role->id, 'permission_id' => $id], ['created_at' => now(), 'updated_at' => now()]);
                }
            }
            $locked->update(['position_setup_completed_at' => now()]);
        });

        app(\App\Services\Core\PermissionService::class)->clearStoreCache((int) $store->id);
        return response()->json(['success' => true]);
    }

    private function ownedStore(Request $request): Store
    {
        abort_unless($request->user()?->hasRole('owner') && $request->user()?->store_id, 403);
        return Store::findOrFail((int) $request->user()->store_id);
    }
}
