<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('supplier_rfq_feedbacks')) {
            return;
        }

        // The original variant-proposal migration is recorded as completed in
        // some installations even though these columns are absent. Each guard
        // makes this repair safe for both complete and incomplete schemas.
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'has_variant')) {
                $table->boolean('has_variant')->default(false);
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_name')) {
                $table->string('variant_name')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'supplier_sku')) {
                $table->string('supplier_sku')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_size')) {
                $table->string('variant_size')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_color')) {
                $table->string('variant_color')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_texture')) {
                $table->string('variant_texture')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_finish')) {
                $table->string('variant_finish')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_material')) {
                $table->string('variant_material')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'unit_of_measurement')) {
                $table->string('unit_of_measurement', 50)->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'variant_image_paths')) {
                $table->json('variant_image_paths')->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'merchandising_status')) {
                $table->string('merchandising_status')->nullable()->index();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'created_variation_id')) {
                $table->unsignedBigInteger('created_variation_id')->nullable()->index();
            }
        });

        if (Schema::hasTable('product_variations')) {
            Schema::table('product_variations', function (Blueprint $table) {
                if (!Schema::hasColumn('product_variations', 'texture')) {
                    $table->string('texture')->nullable();
                }
                if (!Schema::hasColumn('product_variations', 'finish')) {
                    $table->string('finish')->nullable();
                }
            });
        }

        if (Schema::hasTable('purchase_order_items') && !Schema::hasColumn('purchase_order_items', 'quoted_variant_snapshot')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->json('quoted_variant_snapshot')->nullable();
            });
        }
    }

    public function down(): void
    {
        // This is a schema-repair migration. Do not remove columns on rollback,
        // because an installation may have obtained them from the original migration.
    }
};
