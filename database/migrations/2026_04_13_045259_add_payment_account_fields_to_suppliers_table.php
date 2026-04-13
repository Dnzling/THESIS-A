<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name', 120)->nullable()->after('payment_terms');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_name')) {
                $table->string('bank_account_name', 160)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_number')) {
                $table->string('bank_account_number', 80)->nullable()->after('bank_account_name');
            }
            if (!Schema::hasColumn('suppliers', 'bank_account_type')) {
                $table->string('bank_account_type', 40)->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('suppliers', 'bank_branch')) {
                $table->string('bank_branch', 120)->nullable()->after('bank_account_type');
            }
            if (!Schema::hasColumn('suppliers', 'payment_account_updated_at')) {
                $table->timestamp('payment_account_updated_at')->nullable()->after('bank_branch');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_account_type',
                'bank_branch',
                'payment_account_updated_at',
            ] as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $drop[] = $col;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
