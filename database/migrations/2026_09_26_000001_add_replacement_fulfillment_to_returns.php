<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            $table->unsignedInteger('quantity_quarantined')->default(0)->after('quantity_damaged');
        });

        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->string('replacement_status', 32)->nullable();
            $table->foreignId('replacement_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('replacement_inventory_id')->nullable()->constrained('branch_inventory')->nullOnDelete();
            $table->foreignId('replacement_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('replacement_vehicle_id')->nullable()->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
            $table->timestamp('replacement_assigned_at')->nullable();
            $table->timestamp('replacement_dispatched_at')->nullable();
            $table->timestamp('replacement_delivered_at')->nullable();
            $table->string('replacement_proof_path')->nullable();
            $table->string('replacement_received_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_branch_id');
            $table->dropConstrainedForeignId('replacement_inventory_id');
            $table->dropConstrainedForeignId('replacement_driver_id');
            $table->dropConstrainedForeignId('replacement_vehicle_id');
            $table->dropColumn([
                'replacement_status', 'replacement_assigned_at', 'replacement_dispatched_at',
                'replacement_delivered_at', 'replacement_proof_path', 'replacement_received_by',
            ]);
        });
        Schema::table('branch_inventory', fn (Blueprint $table) => $table->dropColumn('quantity_quarantined'));
    }
};
