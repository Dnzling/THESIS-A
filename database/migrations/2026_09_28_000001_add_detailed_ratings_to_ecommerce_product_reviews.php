<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_product_reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('appearance_rating')->nullable()->after('rating');
            $table->unsignedTinyInteger('assembly_rating')->nullable()->after('appearance_rating');
            $table->unsignedTinyInteger('quality_rating')->nullable()->after('assembly_rating');
            $table->unsignedTinyInteger('value_rating')->nullable()->after('quality_rating');
            $table->unsignedTinyInteger('expectations_rating')->nullable()->after('value_rating');
            $table->boolean('is_recommended')->nullable()->after('expectations_rating');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_product_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'appearance_rating',
                'assembly_rating',
                'quality_rating',
                'value_rating',
                'expectations_rating',
                'is_recommended',
            ]);
        });
    }
};
