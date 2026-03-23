<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_address_templates', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_address_templates', 'store_id')) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_address_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_address_templates', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->nullOnDelete();
            }
        });
    }
};

