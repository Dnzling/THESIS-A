<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_subscription_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('plan_key', 80);
            $table->string('plan_name', 120);
            $table->string('billing_cycle', 20);
            $table->decimal('plan_price', 12, 2)->default(0);
            $table->json('plan_terms')->nullable();
            $table->string('agreement_version', 40);
            $table->string('signer_name', 255);
            $table->string('signer_email', 255)->nullable();
            $table->string('signature_path', 500);
            $table->string('document_path', 500)->nullable();
            $table->string('status', 30)->default('pending_activation');
            $table->timestamp('signed_at');
            $table->timestamp('activated_at')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status', 'signed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_subscription_agreements');
    }
};
