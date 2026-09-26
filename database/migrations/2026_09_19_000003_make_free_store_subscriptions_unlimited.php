<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $freePlanIds = DB::table('subscription_plans')
            ->where('plan_key', 'free')
            ->pluck('id');

        if ($freePlanIds->isEmpty()) {
            return;
        }

        $values = ['subscription_ends_at' => null];
        if (Schema::hasColumn('stores', 'trial_started_at')) {
            $values['trial_started_at'] = null;
        }
        if (Schema::hasColumn('stores', 'trial_ends_at')) {
            $values['trial_ends_at'] = null;
        }

        DB::table('stores')
            ->whereIn('subscription_tier', $freePlanIds)
            ->update($values);
    }

    public function down(): void
    {
        // Unlimited Free subscriptions cannot be restored to an accurate former trial date.
    }
};
