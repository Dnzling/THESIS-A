<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('store_delivery_fee_settings', 'return_base_fee')) {
                $table->decimal('return_base_fee', 12, 2)->default(0)->after('base_fee');
            }
            if (!Schema::hasColumn('store_delivery_fee_settings', 'return_per_km_fee')) {
                $table->decimal('return_per_km_fee', 12, 2)->default(0)->after('return_base_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table): void {
            $columns = array_filter(['return_base_fee', 'return_per_km_fee'], fn (string $column): bool => Schema::hasColumn('store_delivery_fee_settings', $column));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
