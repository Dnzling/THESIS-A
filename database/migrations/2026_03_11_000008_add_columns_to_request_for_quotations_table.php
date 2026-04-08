<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('request_for_quotations', 'rfq_type')) {
                $table->enum('rfq_type', ['purchase', 'service', 'both'])->default('purchase')->after('deadline_date');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'currency')) {
                $table->string('currency', 3)->default('PHP')->after('rfq_type');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'payment_terms')) {
                $table->enum('payment_terms', ['net_7', 'net_15', 'net_30', 'net_45', 'net_60', 'cash_on_delivery'])->default('net_30')->after('currency');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'shipping_terms')) {
                $table->enum('shipping_terms', ['FOB', 'CIF', 'EXW', 'DDP'])->nullable()->after('payment_terms');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'instructions')) {
                $table->text('instructions')->nullable()->after('shipping_terms');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'qualification_requirements')) {
                $table->text('qualification_requirements')->nullable()->after('instructions');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('created_by');
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('deadline_date');
            }
            
            if (!Schema::hasColumn('request_for_quotations', 'sent_date')) {
                $table->timestamp('sent_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeignKeyIfExists(['assigned_to']);
            
            // Drop columns
            $table->dropColumn([
                'rfq_type',
                'currency',
                'payment_terms',
                'shipping_terms',
                'instructions',
                'qualification_requirements',
                'assigned_to',
                'expected_delivery_date',
                'sent_date'
            ]);
        });
    }
};
