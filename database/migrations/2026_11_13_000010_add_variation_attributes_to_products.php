<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'variation_attributes')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->json('variation_attributes')->nullable()->after('additional_measurements');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'variation_attributes')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('variation_attributes');
            });
        }
    }
};
