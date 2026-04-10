<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->foreignId('custom_image_id')
                ->nullable()
                ->after('custom_3d_model_id')
                ->constrained('product_assets')
                ->nullOnDelete();

            $table->decimal('length_cm', 10, 2)->nullable()->after('custom_image_id');
            $table->decimal('width_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('width_cm');
            $table->decimal('weight_kg', 10, 2)->nullable()->after('height_cm');
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_image_id');
            $table->dropColumn(['length_cm', 'width_cm', 'height_cm', 'weight_kg']);
        });
    }
};

