<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_products', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->foreignId('source_product_id')->nullable()->after('product_id')
                ->constrained('products')->cascadeOnDelete();
            $table->index(['supplier_id', 'source_product_id'], 'supplier_products_source_lookup');
        });

        Schema::table('supplier_products', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('supplier_products')->whereNull('product_id')->exists()) {
            throw new RuntimeException('Cannot roll back supplier offer matching while unmatched offers exist. Match or remove them first.');
        }

        Schema::table('supplier_products', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropIndex('supplier_products_source_lookup');
            $table->dropConstrainedForeignId('source_product_id');
            $table->foreignId('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
