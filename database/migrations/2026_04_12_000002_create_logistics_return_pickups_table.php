<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_return_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('return_id')->constrained('ecommerce_order_returns')->cascadeOnDelete();
            $table->enum('status', ['scheduled', 'assigned', 'picked_up', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();

            $table->string('pickup_name')->nullable();
            $table->string('pickup_phone')->nullable();
            $table->text('pickup_address')->nullable();

            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->string('proof_photo_path')->nullable();
            $table->string('proof_signature_path')->nullable();
            $table->timestamp('picked_up_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['return_id']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_return_pickups');
    }
};

