<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'government_id_type')) {
                $table->string('government_id_type', 50)->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('employees', 'government_id_number')) {
                $table->string('government_id_number', 100)->nullable()->after('government_id_type');
            }
            if (!Schema::hasColumn('employees', 'government_id_path')) {
                $table->string('government_id_path')->nullable()->after('government_id_number');
            }
            if (!Schema::hasColumn('employees', 'government_id_status')) {
                $table->enum('government_id_status', ['pending', 'verified', 'rejected'])->default('pending')->after('government_id_path');
            }
            if (!Schema::hasColumn('employees', 'government_id_verified_at')) {
                $table->timestamp('government_id_verified_at')->nullable()->after('government_id_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['government_id_verified_at', 'government_id_status', 'government_id_path', 'government_id_number', 'government_id_type'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
