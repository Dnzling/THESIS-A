<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('description');
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reviewed_at');
            }
        });

        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_by')) {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
            }
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_by')) {
                $table->dropColumn('reviewed_by');
            }
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('supplier_rfq_feedbacks', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
