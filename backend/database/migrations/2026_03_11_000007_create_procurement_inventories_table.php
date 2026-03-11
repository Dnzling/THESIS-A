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
        Schema::create('procurement_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            
            // Inventory tracking
            $table->integer('available_qty')->default(0); // Qty available to order
            $table->integer('on_order_qty')->default(0); // Active PO qty
            $table->integer('received_qty')->default(0); // Total received
            $table->integer('pending_receive_qty')->default(0); // Ordered but not yet received
            
            // Status & metadata
            $table->string('status')->default('active'); // active, inactive, discontinued
            $table->timestamp('last_order_date')->nullable();
            $table->timestamp('last_receive_date')->nullable();
            $table->text('notes')->nullable();
            
            // Tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['store_id', 'product_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_inventories');
    }
};
