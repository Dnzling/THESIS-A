<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('system_notifications', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->change();
            }
            if (Schema::hasColumn('system_notifications', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('system_notifications', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('system_notifications', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            }
        });
    }
};
