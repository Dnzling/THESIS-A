<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            if (!Schema::hasColumn('inventory_transactions', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('total_value');
            }

            if (!Schema::hasColumn('inventory_transactions', 'approval_status')) {
                $table->string('approval_status', 30)->nullable()->after('requires_approval');
            }

            if (!Schema::hasColumn('inventory_transactions', 'approval_workflow_id')) {
                $table->foreignId('approval_workflow_id')->nullable()->after('approval_status')
                    ->constrained('approval_workflows')->nullOnDelete();
            }

            if (!Schema::hasColumn('inventory_transactions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_workflow_id')
                    ->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('inventory_transactions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('inventory_transactions', 'approved_at')) {
                $dropColumns[] = 'approved_at';
            }

            if (Schema::hasColumn('inventory_transactions', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('inventory_transactions', 'approval_workflow_id')) {
                $table->dropConstrainedForeignId('approval_workflow_id');
            }

            if (Schema::hasColumn('inventory_transactions', 'approval_status')) {
                $dropColumns[] = 'approval_status';
            }

            if (Schema::hasColumn('inventory_transactions', 'requires_approval')) {
                $dropColumns[] = 'requires_approval';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
