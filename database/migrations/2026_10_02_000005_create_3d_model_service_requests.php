<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_3d_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->string('status', 32)->default('submitted');
            $table->text('materials')->nullable();
            $table->text('notes')->nullable();
            $table->json('reference_photos')->nullable();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('included_revisions')->nullable();
            $table->text('quote_notes')->nullable();
            $table->string('model_path')->nullable();
            $table->foreignId('product_asset_id')->nullable()->constrained('product_assets')->nullOnDelete();
            $table->timestamps();
            $table->index(['owner_store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_3d_requests');
    }
};
