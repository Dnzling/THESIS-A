<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchandising_discount_requests', function (Blueprint $table) {
            $table->foreignId('decided_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('status');
            $table->text('decision_reason')->nullable()->after('decided_at');
            $table->foreignId('promotion_id')->nullable()->after('decision_reason')->constrained('promotions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchandising_discount_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['decided_at', 'decision_reason']);
        });
    }
};
