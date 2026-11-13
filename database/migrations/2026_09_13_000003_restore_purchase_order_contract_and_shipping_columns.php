<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore fields expected by the PO creation flow when the earlier schema
     * migration is recorded but its columns are absent in an existing database.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'supplier_contract_id')) {
                $table->unsignedBigInteger('supplier_contract_id')->nullable();
            }

            if (!Schema::hasColumn('purchase_orders', 'contract_discount_percentage')) {
                $table->decimal('contract_discount_percentage', 5, 2)->default(0);
            }

            if (!Schema::hasColumn('purchase_orders', 'contract_tax_rate')) {
                $table->decimal('contract_tax_rate', 5, 2)->default(0);
            }

            if (!Schema::hasColumn('purchase_orders', 'fulfillment_method')) {
                $table->string('fulfillment_method', 30)->nullable();
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'rfq_item_id')) {
                $table->unsignedBigInteger('rfq_item_id')->nullable();
            }

            if (!Schema::hasColumn('purchase_order_items', 'length_cm')) {
                $table->decimal('length_cm', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('purchase_order_items', 'width_cm')) {
                $table->decimal('width_cm', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('purchase_order_items', 'height_cm')) {
                $table->decimal('height_cm', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('purchase_order_items', 'weight_kg')) {
                $table->decimal('weight_kg', 12, 3)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration repairs live schemas only.
    }
};
