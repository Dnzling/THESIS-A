<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_resolution_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('goods_receipt_resolutions')->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique('goods_receipt_id', 'grrf_receipt_unique');
            $table->index('resolution_id', 'grrf_resolution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_resolution_followups');
    }
};
