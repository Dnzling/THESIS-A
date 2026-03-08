<?php
// backend/database/migrations/2026_03_04_100003_create_branch_procurement_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_procurement_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            
            // Branch can override store settings (if allowed)
            $table->boolean('use_custom_settings')->default(false);
            
            // RFQ threshold override
            $table->decimal('custom_rfq_threshold', 12, 2)->nullable();
            
            // Approval tiers override
            $table->json('custom_approval_tiers')->nullable();
            
            // Transfer settings override
            $table->enum('custom_transfer_policy', [
                'sender_only',
                'both_branches',
                'finance_required',
                'auto_approve'
            ])->nullable();
            
            // Branch budget limits
            $table->decimal('monthly_procurement_budget', 14, 2)->nullable();
            $table->decimal('annual_procurement_budget', 16, 2)->nullable();
            $table->decimal('single_po_limit', 12, 2)->nullable()
                ->comment('Max amount for single PO');
            
            // Branch procurement authority
            $table->decimal('branch_authority_limit', 12, 2)->default(50000.00)
                ->comment('Branch can approve POs up to this amount');
            
            $table->timestamps();
            
            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_procurement_settings');
    }
};