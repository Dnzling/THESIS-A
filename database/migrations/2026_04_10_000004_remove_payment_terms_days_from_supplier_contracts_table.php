<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('supplier_contracts', 'payment_terms_days')) {
            Schema::table('supplier_contracts', function (Blueprint $table) {
                $table->dropColumn('payment_terms_days');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('supplier_contracts', 'payment_terms_days')) {
            Schema::table('supplier_contracts', function (Blueprint $table) {
                $table->integer('payment_terms_days')->default(30)->after('minimum_order_value');
            });
        }
    }
};

