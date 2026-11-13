<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_verification_documents')) {
            Schema::drop('customer_verification_documents');
        }

        if (Schema::hasTable('customers')) {
            try {
                DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_verification_reviewed_by_foreign');
            } catch (Throwable $e) {
                // Older databases may not have this constraint.
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('customers', 'verification_status') ? 'verification_status' : null,
                Schema::hasColumn('customers', 'verification_required') ? 'verification_required' : null,
                Schema::hasColumn('customers', 'verification_trigger_amount') ? 'verification_trigger_amount' : null,
                Schema::hasColumn('customers', 'verification_triggered_at') ? 'verification_triggered_at' : null,
                Schema::hasColumn('customers', 'verification_rejection_reason') ? 'verification_rejection_reason' : null,
                Schema::hasColumn('customers', 'verification_reviewed_by') ? 'verification_reviewed_by' : null,
                Schema::hasColumn('customers', 'verification_reviewed_at') ? 'verification_reviewed_at' : null,
            ]));

            if ($columns) {
                Schema::table('customers', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('users')) {
            try {
                DB::statement('ALTER TABLE users DROP FOREIGN KEY users_customer_verification_reviewed_by_foreign');
            } catch (Throwable $e) {
                // Older databases may not have this constraint.
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'customer_verification_status') ? 'customer_verification_status' : null,
                Schema::hasColumn('users', 'customer_verification_required') ? 'customer_verification_required' : null,
                Schema::hasColumn('users', 'customer_verification_trigger_amount') ? 'customer_verification_trigger_amount' : null,
                Schema::hasColumn('users', 'customer_verification_triggered_at') ? 'customer_verification_triggered_at' : null,
                Schema::hasColumn('users', 'customer_verification_rejection_reason') ? 'customer_verification_rejection_reason' : null,
                Schema::hasColumn('users', 'customer_verification_reviewed_by') ? 'customer_verification_reviewed_by' : null,
                Schema::hasColumn('users', 'customer_verification_reviewed_at') ? 'customer_verification_reviewed_at' : null,
            ]));

            if ($columns) {
                Schema::table('users', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    public function down(): void
    {
        // Customer verification is intentionally not recreated by rollback.
    }
};
