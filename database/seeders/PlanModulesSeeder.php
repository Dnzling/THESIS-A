<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanModulesSeeder extends Seeder
{
    /**
     * Seed plan_modules by including all active modules in every active subscription plan.
     */
    public function run(): void
    {
        $modules = DB::table('modules')->where('is_active', true)->get(['id']);
        $plans = DB::table('subscription_plans')->where('is_active', true)->get(['id']);

        foreach ($plans as $plan) {
            foreach ($modules as $module) {
                DB::table('plan_modules')->updateOrInsert(
                    ['plan_id' => $plan->id, 'module_id' => $module->id],
                    [
                        'included' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
