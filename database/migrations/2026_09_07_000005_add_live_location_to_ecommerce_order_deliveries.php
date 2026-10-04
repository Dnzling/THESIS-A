<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            $table->decimal('current_latitude', 10, 7)->nullable()->after('courier_contact');
            $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            $table->text('current_address')->nullable()->after('current_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude', 'current_address']);
        });
    }
};
