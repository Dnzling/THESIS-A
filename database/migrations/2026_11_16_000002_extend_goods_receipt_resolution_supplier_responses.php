<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_resolutions', function (Blueprint $table) {
            $table->string('supplier_action', 40)->nullable()->after('status');
            $table->text('supplier_response_notes')->nullable()->after('supplier_rejection_reason');
            $table->decimal('supplier_credit_amount', 12, 2)->nullable()->after('supplier_response_notes');
            $table->foreignId('credit_reviewed_by')->nullable()->after('supplier_responded_at')->constrained('users')->nullOnDelete();
            $table->timestamp('credit_reviewed_at')->nullable()->after('credit_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_resolutions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_reviewed_by');
            $table->dropColumn([
                'supplier_action',
                'supplier_response_notes',
                'supplier_credit_amount',
                'credit_reviewed_at',
            ]);
        });
    }
};
