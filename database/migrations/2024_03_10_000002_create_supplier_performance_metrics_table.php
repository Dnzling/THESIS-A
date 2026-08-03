<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        // Create supplier_performance_metrics table
        if (!Schema::hasTable('supplier_performance_metrics')) {
            Schema::create('supplier_performance_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
                $table->date('metric_date');
                $table->integer('on_time_count')->default(0);
                $table->integer('late_count')->default(0);
                $table->decimal('quality_score', 3, 2)->default(5.00);
                $table->integer('average_delivery_days')->default(7);
                $table->integer('delivered_orders')->default(0);
                $table->integer('issues_reported')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->unique(['supplier_id', 'metric_date']);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_performance_metrics')) {
            return;
        }

        Schema::dropIfExists('supplier_performance_metrics');
    }
};
