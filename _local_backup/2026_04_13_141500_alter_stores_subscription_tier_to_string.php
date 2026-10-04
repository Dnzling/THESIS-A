<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // `stores.subscription_tier` is used as a `subscription_plans.plan_key` in code (e.g. ModuleAccessService),
        // so it must allow arbitrary plan keys like `simple` / `unlimited` (not a fixed enum set).
        DB::statement("ALTER TABLE `stores` MODIFY `subscription_tier` VARCHAR(50) NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        // Best-effort rollback: map unknown values to `free` before restoring the enum.
        DB::statement("UPDATE `stores` SET `subscription_tier` = 'free' WHERE `subscription_tier` NOT IN ('free','basic','premium','enterprise')");
        DB::statement("ALTER TABLE `stores` MODIFY `subscription_tier` ENUM('free','basic','premium','enterprise') NOT NULL DEFAULT 'free'");
    }
};

