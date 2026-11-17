<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            if (!Schema::hasColumn('ecommerce_order_returns', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('ecommerce_order_returns', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('ecommerce_order_returns', 'current_address')) {
                $table->text('current_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $columns = collect(['current_latitude', 'current_longitude', 'current_address'])
                ->filter(fn (string $column): bool => Schema::hasColumn('ecommerce_order_returns', $column))
                ->all();
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
