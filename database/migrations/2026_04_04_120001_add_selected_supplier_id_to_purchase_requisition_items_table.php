<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            $table->foreignId('selected_supplier_id')
                ->nullable()
                ->after('variation_id')
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->index('selected_supplier_id', 'pri_selected_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            $table->dropIndex('pri_selected_supplier_idx');
            $table->dropConstrainedForeignId('selected_supplier_id');
        });
    }
};
