<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_item_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_requisition_items')
                ->nullOnDelete();

            $table->integer('allocated_quantity')
                ->nullable()
                ->after('quantity_ordered');

            $table->index('purchase_requisition_item_id', 'poi_requisition_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex('poi_requisition_item_idx');
            $table->dropConstrainedForeignId('purchase_requisition_item_id');
            $table->dropColumn('allocated_quantity');
        });
    }
};
