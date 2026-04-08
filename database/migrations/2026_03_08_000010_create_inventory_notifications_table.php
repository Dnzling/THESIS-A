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
        Schema::create('inventory_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->enum('notification_type', [
                'low_stock_alert',
                'out_of_stock',
                'transfer_request',
                'adjustment_pending',
                'reorder_needed',
                'transfer_approved',
                'transfer_shipped',
                'transfer_received',
                'approval_required'
            ]);
            
            $table->string('entity_type')->comment('stock_alert, stock_transfer, stock_adjustment, etc.');
            $table->bigInteger('entity_id')->nullable();
            
            $table->string('title');
            $table->text('message');
            $table->boolean('action_required')->default(false);
            $table->boolean('is_read')->default(false);
            
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'user_id']);
            $table->index(['store_id', 'branch_id']);
            $table->index(['is_read', 'user_id']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_notifications');
    }
};
