<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->foreignId('replacement_product_id')->nullable()->after('replacement_trip_id')->constrained('products')->nullOnDelete();
            $table->foreignId('replacement_variation_id')->nullable()->after('replacement_product_id')->constrained('product_variations')->nullOnDelete();
            $table->decimal('replacement_unit_price', 12, 2)->nullable()->after('replacement_variation_id');
            $table->decimal('replacement_price_difference', 12, 2)->nullable()->after('replacement_unit_price');
            $table->string('replacement_payment_status', 20)->nullable()->after('replacement_price_difference');
            $table->string('replacement_payment_method', 30)->nullable()->after('replacement_payment_status');
            $table->string('replacement_payment_reference', 120)->nullable()->after('replacement_payment_method');
            $table->foreignId('replacement_customer_accepted_by')->nullable()->after('replacement_payment_reference')->constrained('users')->nullOnDelete();
            $table->timestamp('replacement_customer_accepted_at')->nullable()->after('replacement_customer_accepted_by');
            $table->foreignId('replacement_paid_by')->nullable()->after('replacement_customer_accepted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('replacement_paid_at')->nullable()->after('replacement_paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('replacement_product_id');
            $table->dropConstrainedForeignId('replacement_variation_id');
            $table->dropConstrainedForeignId('replacement_customer_accepted_by');
            $table->dropConstrainedForeignId('replacement_paid_by');
            $table->dropColumn([
                'replacement_unit_price', 'replacement_price_difference', 'replacement_payment_status',
                'replacement_payment_method', 'replacement_payment_reference',
                'replacement_customer_accepted_at', 'replacement_paid_at',
            ]);
        });
    }
};
