<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table) {
            $table->string('truck_brand')->nullable()->after('truck_number');
            $table->string('truck_type')->nullable()->after('truck_brand');
            $table->unsignedSmallInteger('wheel_count')->nullable()->after('truck_type');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table) {
            $table->dropColumn(['truck_brand', 'truck_type', 'wheel_count']);
        });
    }
};
