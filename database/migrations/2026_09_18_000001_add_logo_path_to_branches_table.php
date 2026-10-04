<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('branch_type');
            }
            if (! Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('contact_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('branches', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
