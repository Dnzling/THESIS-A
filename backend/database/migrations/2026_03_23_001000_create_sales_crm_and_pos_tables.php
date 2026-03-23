<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('lead_code', 40)->unique();
            $table->string('full_name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('source', 80)->default('walk_in');
            $table->enum('stage', ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])->default('new');
            $table->decimal('estimated_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'stage']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('sales_crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('sales_crm_leads')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->enum('activity_type', ['note', 'call', 'email', 'meeting', 'stage_change'])->default('note');
            $table->text('description');
            $table->timestamp('activity_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('sales_pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('order_number', 50)->unique();
            $table->enum('status', ['completed', 'voided', 'refunded'])->default('completed');
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'bank_transfer', 'mixed'])->default('cash');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_tendered', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('sales_pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('sales_pos_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->foreignId('branch_inventory_id')->nullable()->constrained('branch_inventory')->nullOnDelete();
            $table->string('product_name', 150);
            $table->string('sku', 80)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('line_tax', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_pos_order_items');
        Schema::dropIfExists('sales_pos_orders');
        Schema::dropIfExists('sales_crm_activities');
        Schema::dropIfExists('sales_crm_leads');
    }
};

