<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->foreignId('pending_cart_id')->nullable()->after('user_id')
                ->constrained('ecommerce_carts')->nullOnDelete();
            $table->json('pending_snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_cart_id');
            $table->dropColumn('pending_snapshot');
        });
    }
};

