<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->decimal('commission_percentage', 5, 2)->default(0)->after('yearly_price');
            $table->unsignedInteger('max_user_accounts')->nullable()->after('commission_percentage');
            $table->unsignedInteger('max_branches')->nullable()->after('max_user_accounts');
            $table->unsignedInteger('max_products')->nullable()->after('max_branches');
            $table->unsignedInteger('max_employees')->nullable()->after('max_products');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropColumn([
                'commission_percentage',
                'max_user_accounts',
                'max_branches',
                'max_products',
                'max_employees',
            ]);
        });
    }
};
