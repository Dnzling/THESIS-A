<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'depth_cm')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('depth_cm', 10, 2)->nullable()->after('height_cm');
            });
        }

        if (!Schema::hasColumn('product_variations', 'depth_cm')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->decimal('depth_cm', 10, 2)->nullable()->after('height_cm');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variations', 'depth_cm')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->dropColumn('depth_cm');
            });
        }

        if (Schema::hasColumn('products', 'depth_cm')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('depth_cm');
            });
        }
    }
};
