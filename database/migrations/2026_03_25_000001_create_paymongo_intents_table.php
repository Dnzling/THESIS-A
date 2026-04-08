<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paymongo_intents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('payment_intent_id')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('PHP');
            $table->string('status')->index();
            $table->string('client_key')->nullable();
            $table->string('description')->nullable();
            $table->string('statement_descriptor')->nullable();
            $table->string('payment_method_allowed')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->json('metadata')->nullable();
            $table->morphs('payable');
            $table->json('webhook_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_intents');
    }
};
