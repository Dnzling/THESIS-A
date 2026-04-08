<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_portals', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_portals', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_portals', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_portals', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
