<?php
// backend/database/migrations/2026_03_19_000003_add_tax_rate_to_purchase_requisition_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisition_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('estimated_unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisition_items', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });
    }
};
