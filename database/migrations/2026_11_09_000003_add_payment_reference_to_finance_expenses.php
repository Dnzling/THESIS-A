<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('finance_expenses', 'payment_reference')) {
            Schema::table('finance_expenses', function (Blueprint $table) {
                $table->string('payment_reference')->nullable()->after('reference_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_expenses', 'payment_reference')) {
            Schema::table('finance_expenses', function (Blueprint $table) {
                $table->dropColumn('payment_reference');
            });
        }
    }
};
