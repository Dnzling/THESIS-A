<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ecommerce_order_deliveries', 'order_deliveries'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'assistant_user_ids')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->json('assistant_user_ids')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['ecommerce_order_deliveries', 'order_deliveries'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'assistant_user_ids')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('assistant_user_ids');
                });
            }
        }
    }
};
