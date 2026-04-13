<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'paid_to_bank_name')) {
                $table->string('paid_to_bank_name', 120)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('invoices', 'paid_to_account_name')) {
                $table->string('paid_to_account_name', 160)->nullable()->after('paid_to_bank_name');
            }
            if (!Schema::hasColumn('invoices', 'paid_to_account_number_masked')) {
                $table->string('paid_to_account_number_masked', 32)->nullable()->after('paid_to_account_name');
            }
            if (!Schema::hasColumn('invoices', 'paid_to_account_type')) {
                $table->string('paid_to_account_type', 40)->nullable()->after('paid_to_account_number_masked');
            }
            if (!Schema::hasColumn('invoices', 'paid_to_bank_branch')) {
                $table->string('paid_to_bank_branch', 120)->nullable()->after('paid_to_account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = [
                'paid_to_bank_name',
                'paid_to_account_name',
                'paid_to_account_number_masked',
                'paid_to_account_type',
                'paid_to_bank_branch',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

