<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_cart_items', 'variation_id')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->foreignId('variation_id')->nullable()->after('product_id')->constrained('product_variations')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('ecommerce_cart_items', 'variation_name')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->string('variation_name')->nullable()->after('variation_id');
            });
        }

        // Keep FK-supporting indexes so MySQL allows dropping the old unique index.
        if (!$this->indexExists('ecommerce_cart_items', 'idx_ecom_cart_items_cart_id')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->index('cart_id', 'idx_ecom_cart_items_cart_id');
            });
        }
        if (!$this->indexExists('ecommerce_cart_items', 'idx_ecom_cart_items_product_id')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->index('product_id', 'idx_ecom_cart_items_product_id');
            });
        }

        if ($this->indexExists('ecommerce_cart_items', 'unq_ecom_cart_item_product')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->dropUnique('unq_ecom_cart_item_product');
            });
        }

        if (!$this->indexExists('ecommerce_cart_items', 'unq_ecom_cart_item_product_variation')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id', 'variation_id'], 'unq_ecom_cart_item_product_variation');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('ecommerce_cart_items', 'unq_ecom_cart_item_product_variation')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->dropUnique('unq_ecom_cart_item_product_variation');
            });
        }

        if (!$this->indexExists('ecommerce_cart_items', 'unq_ecom_cart_item_product')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id'], 'unq_ecom_cart_item_product');
            });
        }

        if (Schema::hasColumn('ecommerce_cart_items', 'variation_id')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('variation_id');
            });
        }

        if (Schema::hasColumn('ecommerce_cart_items', 'variation_name')) {
            Schema::table('ecommerce_cart_items', function (Blueprint $table) {
                $table->dropColumn('variation_name');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }
};
