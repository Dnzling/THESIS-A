<?php

namespace App\Services\Modules;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ModuleAccessService
{
    /**
    * Determine if a module is enabled for a store, applying override -> store -> plan precedence.
    */
    public function isEnabledForStore(int $storeId, string $moduleKey): bool
    {
        $now = Carbon::now();

        // 1) override check
        $override = DB::table('store_module_overrides')
            ->join('modules', 'modules.id', '=', 'store_module_overrides.module_id')
            ->where('modules.key', $moduleKey)
            ->where('store_module_overrides.store_id', $storeId)
            ->where(function ($q) use ($now) {
                $q->whereNull('store_module_overrides.expires_at')
                  ->orWhere('store_module_overrides.expires_at', '>', $now);
            })
            ->select('store_module_overrides.allow')
            ->first();

        if ($override !== null) {
            return (bool) $override->allow;
        }

        // 2) store_modules
        $storeModule = DB::table('store_modules')
            ->join('modules', 'modules.id', '=', 'store_modules.module_id')
            ->where('modules.key', $moduleKey)
            ->where('store_modules.store_id', $storeId)
            ->value('status');

        if ($storeModule !== null) {
            return $storeModule === 'enabled';
        }

        // 3) plan_modules via store plan (fallback)
        $planIncluded = DB::table('stores')
            ->join('subscription_plans', 'subscription_plans.plan_key', '=', 'stores.subscription_tier')
            ->join('plan_modules', 'plan_modules.plan_id', '=', 'subscription_plans.id')
            ->join('modules', 'modules.id', '=', 'plan_modules.module_id')
            ->where('stores.id', $storeId)
            ->where('modules.key', $moduleKey)
            ->value('plan_modules.included');

        return (bool) $planIncluded;
    }

    /**
    * Sync store_modules rows from the store's current pricing plan.
    */
    public function syncStoreModulesFromPlan(int $storeId): void
    {
        $planModules = DB::table('stores')
            ->join('subscription_plans', 'subscription_plans.plan_key', '=', 'stores.subscription_tier')
            ->join('plan_modules', 'plan_modules.plan_id', '=', 'subscription_plans.id')
            ->where('stores.id', $storeId)
            ->select('plan_modules.module_id', 'plan_modules.included')
            ->get();

        foreach ($planModules as $pm) {
            DB::table('store_modules')->updateOrInsert(
                ['store_id' => $storeId, 'module_id' => $pm->module_id],
                [
                    'status' => $pm->included ? 'enabled' : 'disabled',
                    'source' => 'plan',
                    'enabled_at' => $pm->included ? now() : null,
                ]
            );
        }
    }

    /**
     * Return enabled module keys for a store using precedence (override -> store -> plan).
     */
    public function enabledModuleKeysForStore(int $storeId): array
    {
        $keys = DB::table('modules')->where('is_active', true)->pluck('key');
        $enabled = [];
        foreach ($keys as $key) {
            if ($this->isEnabledForStore($storeId, $key)) {
                $enabled[] = $key;
            }
        }
        return $enabled;
    }
}
