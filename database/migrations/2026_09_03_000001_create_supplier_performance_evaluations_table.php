<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('evaluated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('quality_score');
            $table->unsignedTinyInteger('quantity_accuracy_score');
            $table->unsignedTinyInteger('delivery_timeliness_score');
            $table->unsignedTinyInteger('packaging_condition_score');
            $table->decimal('overall_rating', 3, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'supplier_id', 'created_at'], 'supplier_eval_store_supplier_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_evaluations');
    }
};
