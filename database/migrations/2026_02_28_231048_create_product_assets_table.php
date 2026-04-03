<?php
// database/migrations/2024_01_01_000005_create_product_assets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('asset_type', [
                '3D_Model', 
                '3D_Thumbnail',
                'Image_Main', 
                'Image_Gallery', 
                'Image_360', 
                'Video_Product', 
                'Video_Assembly',
                'Manual_PDF',
                'Texture_Map'
            ]);
            
            // File information
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size_kb')->nullable();
            $table->string('mime_type', 100)->nullable();
            
            // 3D specific
            $table->enum('model_format', ['glb', 'gltf', 'obj', 'fbx', 'usdz'])->nullable();
            $table->boolean('is_ar_compatible')->default(false);
            
            // Display settings
            $table->boolean('is_primary')->default(false);
            $table->integer('display_order')->default(0);
            
            // 3D camera settings
            $table->float('default_camera_angle_x')->nullable();
            $table->float('default_camera_angle_y')->nullable();
            $table->float('default_zoom_level')->nullable();
            
            // Metadata
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'asset_type']);
            $table->index(['store_id', 'product_id', 'is_primary']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_assets');
    }
};