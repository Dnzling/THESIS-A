<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->date('requested_fulfillment_date')->nullable();
            $table->string('requested_fulfillment_window', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['requested_fulfillment_date', 'requested_fulfillment_window']);
        });
    }
};
