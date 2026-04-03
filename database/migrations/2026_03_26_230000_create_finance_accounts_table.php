<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name')->default('Main Operating Account');
            $table->enum('type', ['operating', 'payroll', 'petty_cash'])->default('operating');
            $table->string('currency', 3)->default('PHP');
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'type']);
            $table->unique(['store_id', 'type'], 'uniq_store_account_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
