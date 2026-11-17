<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_3d_requests', function (Blueprint $table) {
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('width_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('model_3d_requests', fn (Blueprint $table) => $table->dropColumn(['length_cm', 'width_cm', 'height_cm']));
    }
};
