<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_wholesale_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('business_name', 255);
            $table->text('request_note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'user_id']);
            $table->index(['store_id', 'status']);
        });

        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->foreignId('customer_user_id')->nullable()->after('customer_email')
                ->constrained('users')->nullOnDelete();
            $table->string('customer_response', 20)->nullable()->after('customer_user_id');
            $table->timestamp('customer_responded_at')->nullable()->after('customer_response');
        });

        Schema::create('store_wholesale_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_number', 50)->unique();
            $table->json('items');
            $table->text('customer_note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('wholesale_quote_id')->nullable()->constrained('sales_wholesale_quotes')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'status']);
            $table->index(['customer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_wholesale_quote_requests');

        Schema::table('sales_wholesale_quotes', function (Blueprint $table) {
            $table->dropColumn(['customer_response', 'customer_responded_at']);
            $table->dropConstrainedForeignId('customer_user_id');
        });

        Schema::dropIfExists('store_wholesale_customers');
    }
};
