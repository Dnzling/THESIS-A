<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->index('adjustment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_attachments');
    }
};
