<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->json('additional_variants')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->dropColumn('additional_variants');
        });
    }
};
