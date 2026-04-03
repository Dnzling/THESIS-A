<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('truck_number', 100)->nullable();
            $table->string('plate_number', 50)->nullable();
            $table->string('driver_name', 150);
            $table->string('driver_contact', 50)->nullable();

            $table->text('origin_address')->nullable();
            $table->text('destination_address')->nullable();

            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('cost_per_km', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);

            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();

            $table->enum('status', ['pending', 'in_transit', 'delivered', 'cancelled'])->default('in_transit');

            $table->timestamps();

            $table->unique('purchase_order_id', 'uniq_po_shipments_po');
            $table->index(['supplier_id', 'status'], 'idx_po_shipments_supplier_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_shipments');
    }
};
