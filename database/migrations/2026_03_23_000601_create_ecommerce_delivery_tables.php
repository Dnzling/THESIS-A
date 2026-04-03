<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_delivery_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('vehicle_name', 120);
            $table->enum('vehicle_type', ['motorcycle', 'van', 'truck', 'car', 'other'])->default('van');
            $table->string('plate_number', 50);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('capacity_kg', 10, 2)->nullable();
            $table->unsignedInteger('max_orders_per_trip')->default(10);
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'plate_number'], 'unq_ecom_vehicle_store_plate');
            $table->index(['store_id', 'status']);
        });

        Schema::create('ecommerce_order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            $table->string('tracking_number', 80)->nullable();
            $table->string('courier_name', 120)->nullable();
            $table->string('courier_contact', 50)->nullable();
            $table->enum('status', ['assigned', 'packed', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery', 'cancelled'])->default('assigned');
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->string('proof_of_delivery_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('order_id', 'unq_ecom_order_delivery_order');
            $table->unique('tracking_number', 'unq_ecom_order_delivery_tracking');
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_deliveries');
        Schema::dropIfExists('ecommerce_delivery_vehicles');
    }
};

