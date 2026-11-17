<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_order_shipments', 'trip_id')) {
            Schema::table('purchase_order_shipments', function (Blueprint $table): void {
                $table->foreignId('trip_id')->nullable()->after('purchase_order_id')
                    ->constrained('logistics_delivery_trips')->nullOnDelete();
                $table->index(['trip_id', 'status'], 'idx_po_shipments_trip_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_order_shipments', 'trip_id')) {
            Schema::table('purchase_order_shipments', function (Blueprint $table): void {
                $table->dropIndex('idx_po_shipments_trip_status');
                $table->dropConstrainedForeignId('trip_id');
            });
        }
    }
};
