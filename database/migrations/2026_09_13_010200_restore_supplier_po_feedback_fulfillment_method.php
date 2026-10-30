<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_po_feedbacks') && !Schema::hasColumn('supplier_po_feedbacks', 'fulfillment_method')) {
            Schema::table('supplier_po_feedbacks', function (Blueprint $table) {
                $table->string('fulfillment_method', 30)->nullable()->after('response');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive repair migration: preserve submitted supplier feedback data.
    }
};
