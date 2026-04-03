<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_delivery_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_log_id')->constrained('purchase_order_delivery_logs')->onDelete('cascade');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_delivery_log_attachments');
    }
};
