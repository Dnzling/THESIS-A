<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $freePlanId = DB::table('subscription_plans')->where('plan_key', 'free')->value('id');
        if (!$freePlanId) {
            $freePlanId = DB::table('subscription_plans')->insertGetId([
                'plan_key' => 'free',
                'name' => 'Free',
                'description' => 'Free plan',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'features' => json_encode([
                    'Free trial with limited modules',
                    'Human Resource',
                    'Inventory',
                    'Merchandising',
                    'Sales',
                ]),
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $simplePlanId = DB::table('subscription_plans')->where('plan_key', 'simple')->value('id');
        $unlimitedPlanId = DB::table('subscription_plans')->where('plan_key', 'unlimited')->value('id');

        if (!$simplePlanId) {
            $simplePlanId = $freePlanId;
        }
        if (!$unlimitedPlanId) {
            $unlimitedPlanId = $simplePlanId;
        }

        if (Schema::hasTable('stores')) {
            DB::table('stores')->orderBy('id')->chunkById(500, function ($stores) use ($freePlanId, $simplePlanId, $unlimitedPlanId) {
                foreach ($stores as $store) {
                    $legacyTier = strtolower(trim((string) ($store->subscription_tier ?? 'free')));
                    $planId = match ($legacyTier) {
                        'free', '' => $freePlanId,
                        'basic', 'simple' => $simplePlanId,
                        'premium', 'enterprise', 'unlimited' => $unlimitedPlanId,
                        default => $freePlanId,
                    };

                    DB::table('stores')
                        ->where('id', $store->id)
                        ->update([
                            'subscription_tier' => $planId,
                            'updated_at' => now(),
                        ]);
                }
            }, 'id');

            DB::statement('ALTER TABLE `stores` MODIFY `subscription_tier` BIGINT UNSIGNED NOT NULL');

            try {
                DB::statement('ALTER TABLE `stores` ADD CONSTRAINT `stores_subscription_tier_foreign` FOREIGN KEY (`subscription_tier`) REFERENCES `subscription_plans` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT');
            } catch (\Throwable $e) {
                // Ignore if the foreign key already exists.
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('stores') || !Schema::hasTable('subscription_plans')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `stores` DROP FOREIGN KEY `stores_subscription_tier_foreign`');
        } catch (\Throwable $e) {
            // Ignore if the key does not exist.
        }

        DB::statement('ALTER TABLE `stores` MODIFY `subscription_tier` VARCHAR(50) NOT NULL DEFAULT \'free\'');

        DB::statement('UPDATE `stores` s LEFT JOIN `subscription_plans` p ON p.id = s.subscription_tier SET s.subscription_tier = COALESCE(p.plan_key, \'free\')');
    }
};
