<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable()->change();
        });

        DB::table('products')
            ->where('product_type', '!=', 'finished_good')
            ->update(['category_id' => null, 'subcategory_id' => null]);
    }

    public function down(): void
    {
        if (DB::table('products')->whereNull('category_id')->exists()) {
            throw new RuntimeException('Assign categories to uncategorized products before reverting this migration.');
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
    }
};
