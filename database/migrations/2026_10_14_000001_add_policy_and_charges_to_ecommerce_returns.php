<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->enum('charge_responsibility', ['store', 'customer'])->default('store')->after('return_type');
            $table->decimal('return_fee', 12, 2)->default(0)->after('charge_responsibility');
            $table->json('return_fee_breakdown')->nullable()->after('return_fee');
            $table->string('refund_exception_reason')->nullable()->after('return_fee_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', fn (Blueprint $table) => $table->dropColumn([
            'charge_responsibility', 'return_fee', 'return_fee_breakdown', 'refund_exception_reason',
        ]));
    }
};
