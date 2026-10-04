<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->decimal('available_quantity', 12, 2)->nullable()->after('quoted_price');
            $table->date('estimated_delivery_date')->nullable()->after('available_quantity');
            $table->string('payment_terms')->nullable()->after('estimated_delivery_date');
            $table->date('quotation_valid_until')->nullable()->after('payment_terms');
            $table->string('attachment_path')->nullable()->after('quotation_valid_until');
            $table->text('product_specifications')->nullable()->after('attachment_path');
            $table->text('additional_notes')->nullable()->after('product_specifications');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->dropColumn([
                'available_quantity', 'estimated_delivery_date', 'payment_terms',
                'quotation_valid_until', 'attachment_path', 'product_specifications',
                'additional_notes',
            ]);
        });
    }
};
