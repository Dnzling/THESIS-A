<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_3d_reconstruction_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconstruction_id')
                ->constrained('product_3d_reconstructions')
                ->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size_kb')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_3d_reconstruction_images');
    }
};
