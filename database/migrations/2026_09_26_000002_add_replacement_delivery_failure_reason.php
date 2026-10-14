<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->text('replacement_failure_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', fn (Blueprint $table) => $table->dropColumn('replacement_failure_reason'));
    }
};
