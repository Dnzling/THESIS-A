<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_delivery_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_portal_id')->constrained('supplier_portals')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('truck_brand')->nullable();
            $table->string('truck_type')->nullable();
            $table->unsignedSmallInteger('wheel_count')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->decimal('cost_per_km', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_delivery_templates');
    }
};
