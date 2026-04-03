<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('order_type', 20)->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_contact')->nullable();
            $table->tinyInteger('rating')->default(0);
            $table->text('message')->nullable();
            $table->text('reply')->nullable();
            $table->unsignedBigInteger('replied_by')->nullable()->index();
            $table->timestamp('replied_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_reviews');
    }
};
