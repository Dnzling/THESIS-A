<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->text('details')->nullable();
            $table->enum('status', ['pending_verification', 'approved', 'rejected'])->default('pending_verification');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['store_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ecommerce_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('ecommerce_order_items')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('requested_quantity')->default(1);
            $table->text('reason');
            $table->text('details')->nullable();
            $table->enum('status', ['pending_verification', 'approved', 'rejected', 'received', 'refunded'])->default('pending_verification');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['order_item_id', 'status']);
            $table->index(['store_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ecommerce_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('ecommerce_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->enum('status', ['published', 'hidden'])->default('published');
            $table->timestamps();

            $table->unique(['order_item_id', 'user_id']);
            $table->index(['product_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_reviews');
        Schema::dropIfExists('ecommerce_order_returns');
        Schema::dropIfExists('ecommerce_order_cancellations');
    }
};
