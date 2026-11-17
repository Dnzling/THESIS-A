<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_product_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('parent_category_id')->nullable()->constrained('supplier_product_categories')->cascadeOnDelete();
            $table->string('category_name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['supplier_id', 'parent_category_id', 'category_name'], 'supplier_product_categories_lookup_index');
            $table->index(['supplier_id', 'is_active']);
        });

        $now = now();
        $premade = [
            'Living Room' => ['Sofa', 'Sectional Sofa', 'Coffee Table', 'TV Stand', 'Accent Chair'],
            'Bedroom' => ['Bed', 'Wardrobe', 'Nightstand', 'Dresser'],
            'Dining Room' => ['Dining Table', 'Dining Chair', 'Dining Cabinet'],
            'Office' => ['Office Desk', 'Office Chair', 'Bookcase'],
            'Outdoor' => ['Outdoor Table', 'Patio Chair'],
            'Home Decor' => ['Lighting', 'Rug', 'Wall Decor'],
            'Furniture Materials' => ['Wood', 'Metal', 'Upholstery'],
            'Packaging' => ['Box', 'Protective Wrap'],
            'Other' => [],
        ];
        foreach ($premade as $name => $subcategories) {
            $categoryId = DB::table('supplier_product_categories')->insertGetId([
                'supplier_id' => null,
                'parent_category_id' => null,
                'category_name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($subcategories as $subcategory) {
                DB::table('supplier_product_categories')->insert([
                    'supplier_id' => null,
                    'parent_category_id' => $categoryId,
                    'category_name' => $subcategory,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('supplier_products', function (Blueprint $table): void {
            if (!Schema::hasColumn('supplier_products', 'availability_status')) {
                $table->string('availability_status', 20)->default('available')->after('supplier_price');
            }
        });
        if (Schema::hasColumn('supplier_products', 'available_stock')) {
            DB::table('supplier_products')->where('available_stock', 0)->update(['availability_status' => 'out_of_stock']);
            DB::table('supplier_products')->where('available_stock', '>', 0)->update(['availability_status' => 'available']);
            Schema::table('supplier_products', fn (Blueprint $table) => $table->dropColumn('available_stock'));
        }

        Schema::table('product_variations', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_variations', 'additional_measurements')) {
                $table->json('additional_measurements')->nullable()->after('weight_kg');
            }
        });

        Schema::table('product_assets', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_assets', 'variation_id')) {
                $table->foreignId('variation_id')->nullable()->after('product_id')->constrained('product_variations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('product_assets', 'variation_id')) $table->dropConstrainedForeignId('variation_id');
        });
        Schema::table('product_variations', function (Blueprint $table): void {
            if (Schema::hasColumn('product_variations', 'additional_measurements')) $table->dropColumn('additional_measurements');
        });
        Schema::table('supplier_products', function (Blueprint $table): void {
            if (Schema::hasColumn('supplier_products', 'availability_status')) $table->dropColumn('availability_status');
            if (!Schema::hasColumn('supplier_products', 'available_stock')) $table->unsignedInteger('available_stock')->default(0);
        });
        Schema::dropIfExists('supplier_product_categories');
    }
};
