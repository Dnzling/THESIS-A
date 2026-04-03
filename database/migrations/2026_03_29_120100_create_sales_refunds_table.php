<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('order_type', 20)->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('order_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_refunds');
    }
};
