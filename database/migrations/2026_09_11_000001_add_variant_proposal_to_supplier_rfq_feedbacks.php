<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->boolean('has_variant')->default(false)->after('available_quantity');
            $table->string('variant_name')->nullable()->after('has_variant');
            $table->string('supplier_sku')->nullable()->after('variant_name');
            $table->string('variant_size')->nullable()->after('supplier_sku');
            $table->string('variant_color')->nullable()->after('variant_size');
            $table->string('variant_texture')->nullable()->after('variant_color');
            $table->string('variant_finish')->nullable()->after('variant_texture');
            $table->string('variant_material')->nullable()->after('variant_finish');
            $table->string('unit_of_measurement')->nullable()->after('variant_material');
            $table->json('variant_image_paths')->nullable()->after('attachment_path');
            $table->string('merchandising_status')->nullable()->index()->after('variant_image_paths');
            $table->foreignId('created_variation_id')->nullable()->constrained('product_variations')->nullOnDelete()->after('merchandising_status');
        });
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('texture')->nullable()->after('material');
            $table->string('finish')->nullable()->after('texture');
        });
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->json('quoted_variant_snapshot')->nullable()->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', fn (Blueprint $table) => $table->dropColumn(['texture', 'finish']));
        Schema::table('purchase_order_items', fn (Blueprint $table) => $table->dropColumn('quoted_variant_snapshot'));
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            $table->dropForeign(['created_variation_id']);
            $table->dropColumn(['has_variant', 'variant_name', 'supplier_sku', 'variant_size', 'variant_color', 'variant_texture', 'variant_finish', 'variant_material', 'unit_of_measurement', 'variant_image_paths', 'merchandising_status', 'created_variation_id']);
        });
    }
};
