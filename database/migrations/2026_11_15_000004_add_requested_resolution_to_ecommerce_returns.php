<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->string('requested_resolution', 20)->nullable()->after('details');
            $table->string('preferred_refund_method', 30)->nullable()->after('requested_resolution');
            $table->string('refund_account_name')->nullable()->after('preferred_refund_method');
            $table->string('refund_account_number', 100)->nullable()->after('refund_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->dropColumn([
                'requested_resolution',
                'preferred_refund_method',
                'refund_account_name',
                'refund_account_number',
            ]);
        });
    }
};
