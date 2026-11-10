<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_3d_requests', function (Blueprint $table) {
            $table->date('estimated_completion_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('model_3d_requests', function (Blueprint $table) {
            $table->dropColumn('estimated_completion_date');
        });
    }
};
