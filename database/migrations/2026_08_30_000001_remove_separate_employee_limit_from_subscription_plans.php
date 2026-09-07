<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subscription_plans', 'max_employees')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropColumn('max_employees');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('subscription_plans', 'max_employees')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->unsignedInteger('max_employees')->nullable()->after('max_products');
            });
        }
    }
};
