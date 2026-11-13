<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandising_discount_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->foreignId('branch_inventory_id')->nullable()->constrained('branch_inventory')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 12, 2);
            $table->date('report_period_start');
            $table->date('report_period_end');
            $table->unsignedInteger('units_sold')->default(0);
            $table->unsignedInteger('available_stock')->default(0);
            $table->unsignedInteger('days_cover')->nullable();
            $table->unsignedInteger('days_without_sale')->nullable();
            $table->unsignedInteger('days_listed')->default(0);
            $table->decimal('stock_value', 14, 2)->default(0);
            $table->text('reason');
            $table->string('status', 24)->default('pending');
            $table->timestamps();

            $table->index(['store_id', 'status', 'created_at'], 'mdr_store_status_created_idx');
            $table->index(['branch_id', 'product_id'], 'mdr_branch_product_idx');
        });

        $now = now();
        foreach ([
            'merchandising.vouchers' => 6,
            'merchandising.assets' => 7,
            'merchandising.reports' => 8,
        ] as $name => $order) {
            DB::table('navigation_items')->where('name', $name)->update(['display_order' => $order, 'updated_at' => $now]);
        }
        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'merchandising.discount-requests'],
            [
                'display_name' => 'Discount Requests',
                'module' => 'merchandising',
                'section' => null,
                'route_name' => 'merchandising.discount-requests',
                'route_path' => '/merchandising/discount-requests',
                'icon' => 'pi pi-inbox',
                'parent_id' => null,
                'display_order' => 5,
                'is_active' => true,
                'meta' => json_encode(['subtitle' => 'Review discount proposals from inventory']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.discount-requests')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'merchandising.pricing.view')->value('id');
        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'merchandising.discount-requests')->value('id');
        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
        }

        DB::table('navigation_items')->where('name', 'merchandising.discount-requests')->delete();
        foreach ([
            'merchandising.vouchers' => 5,
            'merchandising.assets' => 6,
            'merchandising.reports' => 7,
        ] as $name => $order) {
            DB::table('navigation_items')->where('name', $name)->update(['display_order' => $order, 'updated_at' => now()]);
        }
        Schema::dropIfExists('merchandising_discount_requests');
    }
};
