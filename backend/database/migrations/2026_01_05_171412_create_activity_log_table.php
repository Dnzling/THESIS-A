<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// In the migration file
public function up(): void
{
    // Schema::create('activity_logs', function (Blueprint $table) {
    //     $table->id();
    //     $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    //     $table->string('action'); // login, logout, create, update, delete
    //     $table->text('description')->nullable();
    //     $table->string('ip_address')->nullable();
    //     $table->text('user_agent')->nullable();
    //     $table->json('data')->nullable(); // Additional data
    //     $table->timestamps();
    // });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
