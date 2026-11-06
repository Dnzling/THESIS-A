<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_refunds', function (Blueprint $table) {
            $table->string('paymongo_transfer_id')->nullable()->unique()->after('paymongo_refund_id');
            $table->string('paymongo_batch_transfer_id')->nullable()->after('paymongo_transfer_id');
            $table->text('transfer_error')->nullable()->after('paymongo_batch_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('finance_refunds', function (Blueprint $table) {
            $table->dropUnique(['paymongo_transfer_id']);
            $table->dropColumn(['paymongo_transfer_id', 'paymongo_batch_transfer_id', 'transfer_error']);
        });
    }
};
