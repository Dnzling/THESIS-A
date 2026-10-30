<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table): void {
            $table->decimal('per_kg_fee', 12, 2)->default(0)->after('per_km_fee');
        });
    }

    public function down(): void
    {
        Schema::table('store_delivery_fee_settings', function (Blueprint $table): void {
            $table->dropColumn('per_kg_fee');
        });
    }
};
