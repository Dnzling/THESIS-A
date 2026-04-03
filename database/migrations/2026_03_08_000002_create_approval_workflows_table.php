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
        Schema::create('approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('workflowable_type');
            $table->unsignedBigInteger('workflowable_id');
            $table->integer('current_step')->default(1);
            $table->string('status', 30)->default('pending');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('workflow_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workflowable_type', 'workflowable_id'], 'idx_aw_polymorphic');
            $table->index(['status', 'created_at'], 'idx_aw_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
