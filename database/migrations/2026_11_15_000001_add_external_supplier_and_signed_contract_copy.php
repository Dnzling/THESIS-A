<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('supplier_portal_id');
        });

        // Existing manually recorded suppliers remain usable as external suppliers.
        DB::table('suppliers')->whereNull('supplier_portal_id')->update(['is_external' => true]);

        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->string('signed_copy_path')->nullable()->after('contract_file_path');
            $table->foreignId('signed_copy_uploaded_by')->nullable()->after('signed_copy_path')->constrained('users')->nullOnDelete();
            $table->timestamp('signed_copy_uploaded_at')->nullable()->after('signed_copy_uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropForeign(['signed_copy_uploaded_by']);
            $table->dropColumn(['signed_copy_path', 'signed_copy_uploaded_by', 'signed_copy_uploaded_at']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_external');
        });
    }
};
