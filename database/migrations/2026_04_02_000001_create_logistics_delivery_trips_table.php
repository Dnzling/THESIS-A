<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_delivery_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['planned', 'in_transit', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('scheduled_departure_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_deliveries', 'trip_id')) {
                $table->foreignId('trip_id')
                    ->nullable()
                    ->after('store_id')
                    ->constrained('logistics_delivery_trips')
                    ->nullOnDelete();
            }
        });

        Schema::table('order_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('order_deliveries', 'trip_id')) {
                $table->foreignId('trip_id')
                    ->nullable()
                    ->after('store_id')
                    ->constrained('logistics_delivery_trips')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('order_deliveries', 'trip_id')) {
                $table->dropConstrainedForeignId('trip_id');
            }
        });

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_order_deliveries', 'trip_id')) {
                $table->dropConstrainedForeignId('trip_id');
            }
        });

        Schema::dropIfExists('logistics_delivery_trips');
    }
};

