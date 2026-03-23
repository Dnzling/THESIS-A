<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            $table->foreignId('driver_user_id')->nullable()->after('vehicle_id')->constrained('users')->nullOnDelete();
            $table->string('proof_signature_path')->nullable()->after('proof_of_delivery_path');
        });

        Schema::create('ecommerce_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('ecommerce_order_deliveries')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->enum('event_type', [
                'created',
                'status_updated',
                'driver_assigned',
                'proof_uploaded',
                'note',
            ])->default('note');
            $table->string('status_from', 50)->nullable();
            $table->string('status_to', 50)->nullable();
            $table->text('message');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['delivery_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_delivery_logs');

        Schema::table('ecommerce_order_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_user_id');
            $table->dropColumn('proof_signature_path');
        });
    }
};

