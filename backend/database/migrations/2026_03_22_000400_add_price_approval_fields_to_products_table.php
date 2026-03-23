<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('price_approval_status', ['approved', 'pending', 'rejected'])
                ->default('approved')
                ->after('discounted_price');
            $table->decimal('pending_base_price', 12, 2)->nullable()->after('price_approval_status');
            $table->decimal('pending_discounted_price', 12, 2)->nullable()->after('pending_base_price');
            $table->unsignedBigInteger('price_proposed_by')->nullable()->after('pending_discounted_price');
            $table->timestamp('price_proposed_at')->nullable()->after('price_proposed_by');
            $table->unsignedBigInteger('price_approved_by')->nullable()->after('price_proposed_at');
            $table->timestamp('price_approved_at')->nullable()->after('price_approved_by');
            $table->unsignedBigInteger('price_rejected_by')->nullable()->after('price_approved_at');
            $table->timestamp('price_rejected_at')->nullable()->after('price_rejected_by');
            $table->text('price_approval_notes')->nullable()->after('price_rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_approval_status',
                'pending_base_price',
                'pending_discounted_price',
                'price_proposed_by',
                'price_proposed_at',
                'price_approved_by',
                'price_approved_at',
                'price_rejected_by',
                'price_rejected_at',
                'price_approval_notes',
            ]);
        });
    }
};

