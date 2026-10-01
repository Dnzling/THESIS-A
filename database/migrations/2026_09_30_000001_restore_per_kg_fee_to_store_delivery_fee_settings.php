<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('store_delivery_fee_settings', 'per_kg_fee')) {
            Schema::table('store_delivery_fee_settings', function (Blueprint $table): void {
                $table->decimal('per_kg_fee', 12, 2)->default(0)->after('per_km_fee');
            });
        }
    }

    public function down(): void
    {
        // Keep the column when rolling back: the existing application code
        // depends on it, and an earlier migration may already have created it.
    }
};
