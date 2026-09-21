<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variations') && !Schema::hasColumn('product_variations', 'cost_price')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->nullable();
            });
        }

        // Preserve existing purchasing estimates for variants created before the
        // variation-level cost was available. Store-specific costs can be edited later.
        if (
            Schema::hasTable('product_variations')
            && Schema::hasColumn('product_variations', 'cost_price')
            && Schema::hasTable('products')
            && Schema::hasColumn('products', 'cost_price')
        ) {
            DB::table('product_variations as variation')
                ->join('products as product', 'product.id', '=', 'variation.product_id')
                ->whereNull('variation.cost_price')
                ->whereNotNull('product.cost_price')
                ->update(['variation.cost_price' => DB::raw('product.cost_price')]);
        }

        // Some installations recorded the original table migration without
        // retaining the table. Recreate it so RFQ detail can load attachments.
        if (!Schema::hasTable('rfq_attachments')) {
            Schema::create('rfq_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')
                    ->constrained('request_for_quotations')
                    ->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('file_type');
                $table->unsignedInteger('file_size');
                $table->enum('attachment_type', ['specification', 'drawing', 'terms', 'other'])
                    ->default('other');
                $table->foreignId('uploaded_by')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep repaired schema and any new attachment/cost data intact on rollback.
    }
};
