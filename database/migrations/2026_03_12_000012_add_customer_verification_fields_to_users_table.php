<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('customer_verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                ->default('unverified')
                ->after('is_active');
            $table->boolean('customer_verification_required')->default(false)->after('customer_verification_status');
            $table->decimal('customer_verification_trigger_amount', 12, 2)->nullable()->after('customer_verification_required');
            $table->timestamp('customer_verification_triggered_at')->nullable()->after('customer_verification_trigger_amount');
            $table->text('customer_verification_rejection_reason')->nullable()->after('customer_verification_triggered_at');
            $table->unsignedBigInteger('customer_verification_reviewed_by')->nullable()->after('customer_verification_rejection_reason');
            $table->timestamp('customer_verification_reviewed_at')->nullable()->after('customer_verification_reviewed_by');

            $table->foreign('customer_verification_reviewed_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_verification_reviewed_by']);
            $table->dropColumn([
                'customer_verification_status',
                'customer_verification_required',
                'customer_verification_trigger_amount',
                'customer_verification_triggered_at',
                'customer_verification_rejection_reason',
                'customer_verification_reviewed_by',
                'customer_verification_reviewed_at',
            ]);
        });
    }
};
