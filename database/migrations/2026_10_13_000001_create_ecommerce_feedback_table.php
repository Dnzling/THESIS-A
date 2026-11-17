<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ecommerce_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('page_url', 500)->nullable();
            $table->string('topic', 40)->default('website');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('message')->nullable();
            $table->boolean('contact_allowed')->default(false);
            $table->string('status', 24)->default('new');
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['topic', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('ecommerce_feedback'); }
};
