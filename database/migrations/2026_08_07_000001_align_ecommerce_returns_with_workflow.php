<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->enum('return_type', ['refund', 'replacement'])->nullable()->after('status');
            $table->enum('product_condition', ['good', 'bad'])->nullable()->after('return_type');
            $table->enum('inventory_disposition', ['resell', 'discard'])->nullable()->after('product_condition');
            $table->unsignedInteger('received_quantity')->nullable()->after('inventory_disposition');
            $table->foreignId('inspected_by')->nullable()->after('received_quantity')->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable()->after('inspected_by');
            $table->text('inspection_notes')->nullable()->after('inspected_at');
            $table->timestamp('resolved_at')->nullable()->after('inspection_notes');
        });

        DB::statement("ALTER TABLE ecommerce_order_returns MODIFY status ENUM('pending_verification','approved','rejected','received','refund_pending','refunded','replaced') NOT NULL DEFAULT 'pending_verification'");
    }

    public function down(): void
    {
        DB::statement("UPDATE ecommerce_order_returns SET status = 'received' WHERE status IN ('refund_pending','replaced')");
        DB::statement("ALTER TABLE ecommerce_order_returns MODIFY status ENUM('pending_verification','approved','rejected','received','refunded') NOT NULL DEFAULT 'pending_verification'");

        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspected_by');
            $table->dropColumn([
                'return_type',
                'product_condition',
                'inventory_disposition',
                'received_quantity',
                'inspected_at',
                'inspection_notes',
                'resolved_at',
            ]);
        });
    }
};
