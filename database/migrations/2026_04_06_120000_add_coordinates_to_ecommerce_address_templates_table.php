<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_address_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_address_templates', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address_line');
            }
            if (!Schema::hasColumn('ecommerce_address_templates', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_address_templates', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_address_templates', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('ecommerce_address_templates', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};