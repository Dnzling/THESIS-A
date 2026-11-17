<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_delivery_trips', function (Blueprint $table): void {
            if (!Schema::hasColumn('logistics_delivery_trips', 'dispatched_by')) {
                $table->foreignId('dispatched_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('logistics_delivery_trips', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable()->after('dispatched_by');
            }
            if (!Schema::hasColumn('logistics_delivery_trips', 'route_distances')) {
                $table->json('route_distances')->nullable()->after('dispatched_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('logistics_delivery_trips', function (Blueprint $table): void {
            if (Schema::hasColumn('logistics_delivery_trips', 'route_distances')) {
                $table->dropColumn('route_distances');
            }
            if (Schema::hasColumn('logistics_delivery_trips', 'dispatched_at')) {
                $table->dropColumn('dispatched_at');
            }
            if (Schema::hasColumn('logistics_delivery_trips', 'dispatched_by')) {
                $table->dropConstrainedForeignId('dispatched_by');
            }
        });
    }
};
