<?php
// backend/database/migrations/2026_03_04_100004_create_role_approval_limits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_approval_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            
            // Approval limits per role
            $table->decimal('max_approval_amount', 12, 2)
                ->comment('Maximum amount this role can approve');
            
            $table->boolean('can_approve_rfq')->default(false);
            $table->boolean('can_create_po')->default(false);
            $table->boolean('can_approve_po')->default(false);
            $table->boolean('can_approve_transfers')->default(false);
            $table->boolean('requires_dual_approval')->default(false)
                ->comment('Needs another approver for POs');
            
            // Module access
            $table->boolean('can_manage_suppliers')->default(false);
            $table->boolean('can_negotiate_prices')->default(false);
            
            $table->timestamps();
            
            $table->unique(['store_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_approval_limits');
    }
};