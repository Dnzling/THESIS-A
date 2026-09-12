<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'ecommerce_icon_path')) {
                $table->string('ecommerce_icon_path')->nullable()->after('icon_path');
            }

            if (!Schema::hasColumn('categories', 'is_ecommerce_quick_select')) {
                $table->boolean('is_ecommerce_quick_select')->default(true)->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'is_ecommerce_quick_select')) {
                $table->dropIndex(['is_ecommerce_quick_select']);
                $table->dropColumn('is_ecommerce_quick_select');
            }

            if (Schema::hasColumn('categories', 'ecommerce_icon_path')) {
                $table->dropColumn('ecommerce_icon_path');
            }
        });
    }
};
