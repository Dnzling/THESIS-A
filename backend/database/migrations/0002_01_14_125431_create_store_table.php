<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table 1: Store Chains/Brands
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('store_code')->unique()->nullable();
            $table->string('province')->default('Cavite');
            $table->string('type', 100);
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // For maps
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->enum('subscription_tier', ['free', 'basic', 'premium', 'enterprise'])->default('free');
            $table->date('subscription_ends_at')->nullable();
            $table->json('settings')->nullable(); // Store specific settings
            $table->timestamps();
        });

        // Table 2: Branches/Physical Locations
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores', 'id');
            $table->string('name', 255);
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 50)->default('Cavite');
            $table->decimal('latitude', 10, 8)->nullable(); // For maps
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_number');
            $table->string('branch_code', 20)->unique(); // e.g., "MF-DASMA-01"
            $table->boolean('is_main_branch')->default(false);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('stores');
    }
};
