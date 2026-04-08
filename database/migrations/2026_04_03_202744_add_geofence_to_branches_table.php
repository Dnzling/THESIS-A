<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'barangay')) {
                $table->string('barangay', 150)->nullable()->after('city');
            }
            if (!Schema::hasColumn('branches', 'geofence_radius_m')) {
                $table->unsignedInteger('geofence_radius_m')->default(5)->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'geofence_radius_m')) {
                $table->dropColumn('geofence_radius_m');
            }
            if (Schema::hasColumn('branches', 'barangay')) {
                $table->dropColumn('barangay');
            }
        });
    }
};