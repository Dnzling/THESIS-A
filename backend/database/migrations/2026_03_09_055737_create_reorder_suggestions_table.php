<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reorder_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reorder_rule_id')->constrained('reorder_rules')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('suggestion_type', ['automatic', 'manual', 'forecast', 'seasonal', 'emergency']);
            $table->decimal('current_stock', 10, 2);
            $table->decimal('suggested_quantity', 10, 2);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['pending', 'approved', 'rejected', 'implemented', 'cancelled'])->default('pending');
            $table->text('reason');
            $table->json('metadata')->nullable(); // Additional data like forecast info, seasonal adjustments, etc.
            $table->timestamp('suggested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('implemented_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->text('implementation_notes')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['suggestion_type', 'status']);
            $table->index(['priority', 'status']);
            $table->index('suggested_at');
            $table->index('valid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_suggestions');
    }
};
