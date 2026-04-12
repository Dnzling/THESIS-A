<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verification_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_verification_documents', 'id_type')) {
                $table->string('id_type', 60)->nullable()->after('document_type');
            }

            if (!Schema::hasColumn('customer_verification_documents', 'id_number')) {
                $table->string('id_number', 120)->nullable()->after('id_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_verification_documents', function (Blueprint $table) {
            if (Schema::hasColumn('customer_verification_documents', 'id_number')) {
                $table->dropColumn('id_number');
            }

            if (Schema::hasColumn('customer_verification_documents', 'id_type')) {
                $table->dropColumn('id_type');
            }
        });
    }
};
