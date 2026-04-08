<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_pos_orders', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled'])
                    ->default('pending')
                    ->after('payment_method');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'payment_channel')) {
                $table->string('payment_channel', 30)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable()->after('payment_channel');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('sales_pos_orders', 'receipt_number')) {
                $table->string('receipt_number', 80)->nullable()->after('paid_at');
            }
        });

        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed','cod') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_pos_orders MODIFY status ENUM('pending_payment','completed','voided','refunded') NOT NULL DEFAULT 'pending_payment'");

        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_pos_orders')->cascadeOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('sales_crm_leads')->nullOnDelete();
            $table->enum('payment_provider', ['manual', 'paymongo'])->default('manual');
            $table->enum('payment_method', ['cash', 'card', 'gcash', 'cod'])->default('cash');
            $table->string('currency', 10)->default('PHP');
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'awaiting_payment_method', 'processing', 'paid', 'failed', 'cancelled', 'expired'])->default('pending');
            $table->string('provider_reference', 120)->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['sales_order_id', 'status']);
        });

        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_pos_orders')->cascadeOnDelete();
            $table->foreignId('sales_payment_id')->nullable()->constrained('sales_payments')->nullOnDelete();
            $table->string('receipt_number', 80)->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('PHP');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'issued_at']);
            $table->unique('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_receipts');
        Schema::dropIfExists('sales_payments');

        Schema::table('sales_pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_pos_orders', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            if (Schema::hasColumn('sales_pos_orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('sales_pos_orders', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('sales_pos_orders', 'payment_channel')) {
                $table->dropColumn('payment_channel');
            }
            if (Schema::hasColumn('sales_pos_orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });

        DB::statement("ALTER TABLE sales_pos_orders MODIFY payment_method ENUM('cash','card','gcash','bank_transfer','mixed') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE sales_pos_orders MODIFY status ENUM('completed','voided','refunded') NOT NULL DEFAULT 'completed'");
    }
};
