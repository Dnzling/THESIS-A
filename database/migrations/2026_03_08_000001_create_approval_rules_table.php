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
        Schema::create('approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('trigger_event');
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->integer('priority')->default(100);
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['trigger_event', 'is_active', 'priority'], 'idx_ar_trigger_active_priority');
            $table->index(['store_id', 'is_active'], 'idx_ar_store_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};
