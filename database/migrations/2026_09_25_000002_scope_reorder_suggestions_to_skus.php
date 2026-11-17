<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reorder_suggestions', function (Blueprint $table): void {
            $table->dropForeign(['reorder_rule_id']);
            $table->foreignId('reorder_rule_id')->nullable()->change();
            $table->foreign('reorder_rule_id')
                ->references('id')->on('reorder_rules')->nullOnDelete();
            $table->foreignId('variation_id')->nullable()->after('product_id')
                ->constrained('product_variations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('reorder_suggestions')->whereNull('reorder_rule_id')->exists()) {
            throw new \RuntimeException(
                'Cannot make reorder_rule_id required while suggestions without a rule still exist.'
            );
        }

        Schema::table('reorder_suggestions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('variation_id');
            $table->dropForeign(['reorder_rule_id']);
            $table->foreignId('reorder_rule_id')->nullable(false)->change();
            $table->foreign('reorder_rule_id')
                ->references('id')->on('reorder_rules')->cascadeOnDelete();
        });
    }
};
