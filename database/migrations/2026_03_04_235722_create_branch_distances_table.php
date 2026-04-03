<?php
// backend/database/migrations/2026_03_04_100025_create_branch_distances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_distances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('to_branch_id')->constrained('branches')->onDelete('cascade');
            
            $table->decimal('distance_km', 8, 2);
            $table->integer('estimated_travel_time_minutes')->nullable();
            $table->enum('route_type', ['highway', 'city', 'mixed'])->default('mixed');
            
            // Can be auto-calculated using Google Maps API or manual entry
            $table->boolean('auto_calculated')->default(false);
            $table->timestamp('last_calculated_at')->nullable();
            
            $table->timestamps();
            
            // Distance is bidirectional, but we store both for easier queries
            $table->unique(['from_branch_id', 'to_branch_id']);
            $table->index(['store_id', 'from_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_distances');
    }
};