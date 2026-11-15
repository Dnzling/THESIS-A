<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_variations')) {
            return;
        }

        if (!Schema::hasColumn('product_variations', 'is_baseline')) {
            Schema::table('product_variations', function (Blueprint $table): void {
                $table->boolean('is_baseline')->default(false);
            });
        }

        DB::table('product_variations')
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(variation_name)) = ?', ['standard'])
            ->update(['is_baseline' => true]);
    }

    public function down(): void
    {
        if (Schema::hasTable('product_variations') && Schema::hasColumn('product_variations', 'is_baseline')) {
            Schema::table('product_variations', function (Blueprint $table): void {
                $table->dropColumn('is_baseline');
            });
        }
    }
};
