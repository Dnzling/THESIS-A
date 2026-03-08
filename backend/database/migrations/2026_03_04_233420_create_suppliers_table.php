<?php
// backend/database/migrations/2026_03_04_100006_create_suppliers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('supplier_code', 50)->unique(); // e.g., "SUP-2026-001"
            $table->string('supplier_name');
            $table->string('company_name')->nullable();
            
            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50);
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website')->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Philippines');
            
            // Business Details
            $table->string('tin', 50)->nullable(); // Tax Identification Number
            $table->string('business_registration', 100)->nullable();
            $table->enum('supplier_type', [
                'manufacturer',
                'wholesaler',
                'distributor',
                'importer',
                'local_artisan'
            ])->default('wholesaler');
            
            // Payment Terms
            $table->enum('payment_terms', [
                'cash_on_delivery',
                'net_7',
                'net_15',
                'net_30',
                'net_60',
                'advance_payment'
            ])->default('net_30');
            
            $table->decimal('credit_limit', 12, 2)->nullable(); // Maximum credit allowed
            $table->decimal('current_balance', 12, 2)->default(0); // Outstanding balance
            
            // Performance Tracking
            $table->decimal('rating', 3, 2)->default(5.00); // 1.00 to 5.00
            $table->integer('total_orders')->default(0);
            $table->decimal('total_amount_purchased', 14, 2)->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            
            // Status
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'status']);
            $table->index('supplier_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};