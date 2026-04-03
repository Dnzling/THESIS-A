<?php
// backend/database/migrations/2026_03_04_100026_create_stock_alerts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            
            $table->enum('alert_type', [
                'low_stock',
                'out_of_stock',
                'overstock',
                'reorder_needed',
                'expired_soon'
            ]);
            
            $table->integer('current_quantity');
            $table->integer('reorder_point')->nullable();
            $table->integer('recommended_order_quantity')->nullable();
            
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->foreignId('acknowledged_by')->nullable()->constrained('employees');
            $table->timestamp('acknowledged_at')->nullable();
            
            $table->timestamps();
            
            // ✅ FIXED: Custom index name
            $table->index(['branch_id', 'status', 'alert_type'], 'idx_salert_branch_status_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};