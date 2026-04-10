<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_contracts', 'termination_request_status')) {
                $table->string('termination_request_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_requested_by_user_id')) {
                $table->unsignedBigInteger('termination_requested_by_user_id')->nullable()->after('termination_request_status');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_requested_by_type')) {
                $table->string('termination_requested_by_type', 30)->nullable()->after('termination_requested_by_user_id');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_request_reason')) {
                $table->text('termination_request_reason')->nullable()->after('termination_requested_by_type');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_requested_at')) {
                $table->timestamp('termination_requested_at')->nullable()->after('termination_request_reason');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_response_by_user_id')) {
                $table->unsignedBigInteger('termination_response_by_user_id')->nullable()->after('termination_requested_at');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_response_notes')) {
                $table->text('termination_response_notes')->nullable()->after('termination_response_by_user_id');
            }
            if (!Schema::hasColumn('supplier_contracts', 'termination_responded_at')) {
                $table->timestamp('termination_responded_at')->nullable()->after('termination_response_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'termination_request_status',
                'termination_requested_by_user_id',
                'termination_requested_by_type',
                'termination_request_reason',
                'termination_requested_at',
                'termination_response_by_user_id',
                'termination_response_notes',
                'termination_responded_at',
            ]);
        });
    }
};

