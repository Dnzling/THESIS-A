<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('source', 60)->default('subscription_upgrade');
            $table->string('reference', 191)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PHP');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->index(['store_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
    }
};
