<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_3d_reconstructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['queued', 'processing', 'ready', 'failed', 'canceled'])->default('queued');
            $table->unsignedInteger('input_count')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);

            $table->string('output_path')->nullable();
            $table->enum('output_format', ['glb', 'obj', 'ply'])->nullable();
            $table->foreignId('output_asset_id')->nullable()->constrained('product_assets')->nullOnDelete();

            $table->json('options')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_3d_reconstructions');
    }
};
