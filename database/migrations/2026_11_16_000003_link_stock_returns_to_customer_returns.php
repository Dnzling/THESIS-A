<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_returns', function (Blueprint $table): void {
            $table->foreignId('ecommerce_return_id')->nullable()->unique()
                ->after('store_id')->constrained('ecommerce_order_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_returns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ecommerce_return_id');
        });
    }
};
