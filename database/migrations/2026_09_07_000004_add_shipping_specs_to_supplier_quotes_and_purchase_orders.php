<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->decimal('length_cm', 10, 2)->nullable()->after('available_quantity');
            $table->decimal('width_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('width_cm');
            $table->decimal('weight_kg', 12, 3)->nullable()->after('height_cm');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('rfq_item_id')->nullable()->after('purchase_requisition_item_id');
            $table->decimal('length_cm', 10, 2)->nullable()->after('unit_cost');
            $table->decimal('width_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('width_cm');
            $table->decimal('weight_kg', 12, 3)->nullable()->after('height_cm');
            $table->foreign('rfq_item_id')->references('id')->on('rfq_items')->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_contract_id')->nullable()->after('supplier_quotation_id');
            $table->decimal('contract_discount_percentage', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('contract_tax_rate', 5, 2)->default(0)->after('contract_discount_percentage');
            $table->string('fulfillment_method', 30)->nullable()->after('expected_delivery_date');
            $table->foreign('supplier_contract_id')->references('id')->on('supplier_contracts')->nullOnDelete();
        });

        Schema::table('supplier_po_feedbacks', function (Blueprint $table) {
            $table->string('fulfillment_method', 30)->nullable()->after('response');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_po_feedbacks', function (Blueprint $table) {
            $table->dropColumn('fulfillment_method');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_contract_id']);
            $table->dropColumn([
                'supplier_contract_id',
                'contract_discount_percentage',
                'contract_tax_rate',
                'fulfillment_method',
            ]);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['rfq_item_id']);
            $table->dropColumn(['rfq_item_id', 'length_cm', 'width_cm', 'height_cm', 'weight_kg']);
        });

        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['length_cm', 'width_cm', 'height_cm', 'weight_kg']);
        });
    }
};
