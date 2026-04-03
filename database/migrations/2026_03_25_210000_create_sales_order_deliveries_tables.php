<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_pos_orders')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tracking_number')->nullable()->index();
            $table->string('courier_name')->nullable();
            $table->string('courier_contact')->nullable();
            $table->string('status')->default('assigned')->index();

            $table->timestamp('scheduled_delivery_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->string('failed_reason', 1000)->nullable();
            $table->text('notes')->nullable();

            $table->string('proof_of_delivery_path')->nullable();
            $table->string('proof_signature_path')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('order_deliveries')->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_pos_orders')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->string('message');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_delivery_logs');
        Schema::dropIfExists('order_deliveries');
    }
};
