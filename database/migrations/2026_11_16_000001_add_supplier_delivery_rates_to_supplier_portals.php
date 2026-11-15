<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_portals', function (Blueprint $table): void {
            if (!Schema::hasColumn('supplier_portals', 'delivery_fee_per_km')) {
                $table->decimal('delivery_fee_per_km', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('supplier_portals', 'delivery_fee_per_kg')) {
                $table->decimal('delivery_fee_per_kg', 12, 2)->nullable();
            }
        });

        if (!Schema::hasColumn('purchase_orders', 'supplier_fulfillment_status')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->string('supplier_fulfillment_status', 30)->nullable()->after('fulfillment_method');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'shipping_fee_breakdown')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->json('shipping_fee_breakdown')->nullable()->after('shipping_cost');
            });
        }
        if (Schema::hasColumn('purchase_orders', 'fulfillment_method')) {
            \Illuminate\Support\Facades\DB::table('purchase_orders')
                ->whereNull('fulfillment_method')
                ->update(['fulfillment_method' => 'store_pickup']);
        }
        if (Schema::hasColumn('purchase_orders', 'supplier_fulfillment_status')) {
            \Illuminate\Support\Facades\DB::table('purchase_orders')
                ->where('status', 'supplier_accepted')
                ->whereNull('supplier_fulfillment_status')
                ->update(['supplier_fulfillment_status' => 'preparing']);
        }
        if (!Schema::hasTable('supplier_po_fulfillment_updates')) {
            Schema::create('supplier_po_fulfillment_updates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('supplier_portal_id')->constrained('supplier_portals')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30);
                $table->text('notes')->nullable();
                $table->json('photo_paths')->nullable();
                $table->timestamps();
            });
        }
        // Keep the name below MySQL's 64-character identifier limit. If a
        // previous attempt created the table before failing to add its index,
        // this also safely completes that partial migration on retry.
        if (Schema::hasTable('supplier_po_fulfillment_updates')
            && !Schema::hasIndex('supplier_po_fulfillment_updates', 'supplier_po_fulfillment_po_created_idx')) {
            Schema::table('supplier_po_fulfillment_updates', function (Blueprint $table): void {
                $table->index(['purchase_order_id', 'created_at'], 'supplier_po_fulfillment_po_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_po_fulfillment_updates');
        if (Schema::hasColumn('purchase_orders', 'supplier_fulfillment_status')) {
            Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropColumn('supplier_fulfillment_status'));
        }
        if (Schema::hasColumn('purchase_orders', 'shipping_fee_breakdown')) {
            Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropColumn('shipping_fee_breakdown'));
        }
        Schema::table('supplier_portals', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('supplier_portals', 'delivery_fee_per_km') ? 'delivery_fee_per_km' : null,
                Schema::hasColumn('supplier_portals', 'delivery_fee_per_kg') ? 'delivery_fee_per_kg' : null,
            ]);
            if ($columns) $table->dropColumn($columns);
        });
    }
};
