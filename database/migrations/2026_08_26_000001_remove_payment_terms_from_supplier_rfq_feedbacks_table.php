<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('supplier_rfq_feedbacks', 'payment_terms')) {
            Schema::table('supplier_rfq_feedbacks', fn (Blueprint $table) => $table->dropColumn('payment_terms'));
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('supplier_rfq_feedbacks', 'payment_terms')) {
            Schema::table('supplier_rfq_feedbacks', fn (Blueprint $table) => $table->string('payment_terms')->nullable());
        }
    }
};
