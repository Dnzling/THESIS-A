<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('ecommerce_icon_path')->nullable()->after('icon_path');
            $table->boolean('is_ecommerce_quick_select')->default(true)->after('ecommerce_icon_path')->index();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_ecommerce_quick_select']);
            $table->dropColumn(['ecommerce_icon_path', 'is_ecommerce_quick_select']);
        });
    }
};
