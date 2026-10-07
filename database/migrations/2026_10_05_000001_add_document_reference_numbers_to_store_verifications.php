<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_verifications', function (Blueprint $table) {
            $table->string('tax_certificate_number', 100)->nullable();
            $table->string('permit_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_verifications', function (Blueprint $table) {
            $table->dropColumn(['tax_certificate_number', 'permit_number']);
        });
    }
};
