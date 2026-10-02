<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->string('customization_status', 20)->nullable()->after('customization_request');
            $table->text('customization_response')->nullable()->after('customization_status');
            $table->foreignId('customization_reviewed_by')->nullable()->after('customization_response')->constrained('users')->nullOnDelete();
            $table->timestamp('customization_reviewed_at')->nullable()->after('customization_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customization_reviewed_by');
            $table->dropColumn(['customization_status', 'customization_response', 'customization_reviewed_at']);
        });
    }
};
