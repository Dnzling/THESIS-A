<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE logistics_return_pickups MODIFY status ENUM('scheduled','ready_for_dispatch','assigned','picked_up','in_transit','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'ready_for_dispatch'");
        Schema::table('logistics_return_pickups', function (Blueprint $table) {
            $table->foreignId('destination_branch_id')->nullable()->after('vehicle_id')->constrained('branches')->nullOnDelete();
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->text('current_address')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('logistics_return_pickup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_pickup_id')->constrained('logistics_return_pickups')->cascadeOnDelete();
            $table->string('event_type')->default('status_updated');
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->text('message');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('location_address')->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_return_pickup_logs');
        DB::table('logistics_return_pickups')->whereIn('status', ['in_transit', 'out_for_delivery', 'delivered'])->update(['status' => 'picked_up']);
        DB::statement("ALTER TABLE logistics_return_pickups MODIFY status ENUM('scheduled','ready_for_dispatch','assigned','picked_up','cancelled') NOT NULL DEFAULT 'ready_for_dispatch'");
        Schema::table('logistics_return_pickups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_branch_id');
            $table->dropColumn(['current_latitude', 'current_longitude', 'current_address', 'out_for_delivery_at', 'delivered_at']);
        });
    }
};
