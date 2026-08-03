<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'deactivation_reason')) {
                $table->text('deactivation_reason')->nullable()->after('subscription_ends_at');
            }

            if (!Schema::hasColumn('stores', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('deactivation_reason');
            }

            if (!Schema::hasColumn('stores', 'deactivated_by')) {
                $table->unsignedBigInteger('deactivated_by')->nullable()->after('deactivated_at');
            }

            if (!Schema::hasColumn('stores', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'deactivated_by')) {
                $table->dropColumn('deactivated_by');
            }

            if (Schema::hasColumn('stores', 'deactivated_at')) {
                $table->dropColumn('deactivated_at');
            }

            if (Schema::hasColumn('stores', 'deactivation_reason')) {
                $table->dropColumn('deactivation_reason');
            }

            if (Schema::hasColumn('stores', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
