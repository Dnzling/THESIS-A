<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_orders', 'assigned_branch_id')) {
                $table->foreignId('assigned_branch_id')->nullable()->after('store_id')->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('ecommerce_orders', 'customer_latitude')) {
                $table->decimal('customer_latitude', 10, 7)->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('ecommerce_orders', 'customer_longitude')) {
                $table->decimal('customer_longitude', 10, 7)->nullable()->after('customer_latitude');
            }
        });

        Schema::create('ecommerce_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'customer_user_id'], 'ecom_chat_threads_store_customer_unique');
            $table->index(['customer_user_id', 'last_message_at']);
            $table->index(['store_id', 'last_message_at']);
        });

        Schema::create('ecommerce_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('ecommerce_chat_threads')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_role', ['customer', 'store']);
            $table->foreignId('order_id')->nullable()->constrained('ecommerce_orders')->nullOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_chat_messages');
        Schema::dropIfExists('ecommerce_chat_threads');

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_orders', 'assigned_branch_id')) {
                $table->dropConstrainedForeignId('assigned_branch_id');
            }
            if (Schema::hasColumn('ecommerce_orders', 'customer_latitude')) {
                $table->dropColumn('customer_latitude');
            }
            if (Schema::hasColumn('ecommerce_orders', 'customer_longitude')) {
                $table->dropColumn('customer_longitude');
            }
        });
    }
};

