<?php
// backend/database/migrations/2026_03_04_100002_create_procurement_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            
            // ========== RFQ CONFIGURATION ==========
            $table->enum('rfq_policy', [
                'always',           // Always require RFQ
                'never',            // Never use RFQ (direct orders only)
                'amount_based',     // Based on amount threshold
                'contract_based'    // Only if no existing contract
            ])->default('amount_based');
            
            $table->decimal('rfq_threshold_amount', 12, 2)->default(50000.00)
                ->comment('Trigger RFQ when PO amount >= this');
            
            $table->integer('rfq_minimum_suppliers')->default(3)
                ->comment('Minimum suppliers to invite for RFQ');
            
            $table->integer('rfq_response_days')->default(7)
                ->comment('Days for suppliers to respond');
            
            $table->boolean('rfq_skip_if_contract')->default(true)
                ->comment('Skip RFQ if active contract exists');
            
            // ========== FINANCE APPROVAL TIERS ==========
            $table->json('approval_tiers')->nullable()
                ->comment('JSON: [{"max_amount": 20000, "level": 1, "approvers": ["warehouse_manager"]}, ...]');
            
            // ========== BRANCH-SPECIFIC OVERRIDES ==========
            $table->boolean('allow_branch_overrides')->default(true)
                ->comment('Can branches set their own thresholds?');
            
            // ========== INTER-BRANCH TRANSFER CONFIG ==========
            $table->enum('transfer_approval_policy', [
                'sender_only',      // Only sender branch approves
                'both_branches',    // Both sender and receiver must approve
                'finance_required', // Finance department must approve
                'auto_approve'      // Automatic approval
            ])->default('sender_only');
            
            $table->enum('transfer_cost_method', [
                'none',             // No cost tracking
                'distance_based',   // Calculate based on distance
                'manual_entry',     // Staff enters cost manually
                'fixed_fee',        // Fixed fee per transfer
                'value_percentage'  // % of goods value
            ])->default('distance_based');
            
            $table->decimal('transfer_fixed_fee', 10, 2)->default(500.00)
                ->comment('Fixed fee per transfer (if fixed_fee method)');
            
            $table->decimal('transfer_cost_per_km', 8, 2)->default(15.00)
                ->comment('Cost per kilometer (if distance_based)');
            
            $table->decimal('transfer_value_percentage', 5, 2)->default(2.00)
                ->comment('% of value as transfer cost (if value_percentage)');
            
            $table->decimal('transfer_approval_threshold', 12, 2)->nullable()
                ->comment('Transfer value requiring finance approval');
            
            // ========== SUPPLIER EVALUATION ==========
            $table->boolean('auto_evaluate_suppliers')->default(true);
            $table->integer('min_orders_for_rating')->default(5);
            
            // ========== PAYMENT TERMS ==========
            $table->enum('default_payment_terms', [
                'cash_on_delivery',
                'net_7',
                'net_15',
                'net_30',
                'net_60'
            ])->default('net_30');
            
            $table->timestamps();
            
            // Only one config per store
            $table->unique('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_settings');
    }
};