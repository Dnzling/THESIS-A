<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table): void {
            $table->foreignId('driver_employee_id')->nullable()->after('created_by')->constrained('employees')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()->after('driver_employee_id')->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('driver_user_id')->constrained('ecommerce_delivery_vehicles')->nullOnDelete();
        });

        DB::table('purchase_order_shipments as shipments')
            ->join('purchase_orders as orders', 'orders.id', '=', 'shipments.purchase_order_id')
            ->select('shipments.id', 'shipments.driver_name', 'shipments.plate_number', 'orders.store_id')
            ->orderBy('shipments.id')
            ->each(function ($shipment): void {
                $driverName = mb_strtolower(trim((string) $shipment->driver_name));
                $driver = DB::table('employees')
                    ->join('users', 'users.id', '=', 'employees.user_id')
                    ->where('employees.store_id', $shipment->store_id)
                    ->whereNull('employees.deleted_at')
                    ->select('employees.id as employee_id', 'users.id as user_id', 'users.fname', 'users.lname')
                    ->get()
                    ->first(fn ($employee) => mb_strtolower(trim("{$employee->fname} {$employee->lname}")) === $driverName);

                $vehicleId = DB::table('ecommerce_delivery_vehicles')
                    ->where('store_id', $shipment->store_id)
                    ->where('plate_number', $shipment->plate_number)
                    ->value('id');

                DB::table('purchase_order_shipments')->where('id', $shipment->id)->update([
                    'driver_employee_id' => $driver?->employee_id,
                    'driver_user_id' => $driver?->user_id,
                    'vehicle_id' => $vehicleId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_order_shipments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('driver_user_id');
            $table->dropConstrainedForeignId('driver_employee_id');
        });
    }
};
