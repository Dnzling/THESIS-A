<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_verifications', function (Blueprint $table) {
            $table->date('registration_expires_at')->nullable();
            $table->date('tax_expires_at')->nullable();
            $table->date('permit_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_verifications', function (Blueprint $table) {
            $table->dropColumn(['registration_expires_at', 'tax_expires_at', 'permit_expires_at']);
        });
    }
};
