<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_contracts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('supplier_contracts', 'rejected_by_user_id')) {
                $table->unsignedBigInteger('rejected_by_user_id')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('supplier_contracts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_contracts', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('supplier_contracts', 'rejected_by_user_id')) {
                $table->dropColumn('rejected_by_user_id');
            }
            if (Schema::hasColumn('supplier_contracts', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};

