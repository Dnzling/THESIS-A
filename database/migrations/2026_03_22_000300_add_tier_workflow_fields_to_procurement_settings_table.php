<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_settings', function (Blueprint $table) {
            $table->enum('business_size', ['small', 'medium', 'enterprise'])
                ->default('medium')
                ->after('store_id');

            $table->enum('workflow_mode', ['simple', 'standard', 'strict'])
                ->default('standard')
                ->after('business_size');

            $table->boolean('allow_self_approval')
                ->default(false)
                ->after('approval_tiers');

            $table->decimal('self_approval_threshold', 12, 2)
                ->nullable()
                ->after('allow_self_approval');

            $table->boolean('enforce_separation_of_duties')
                ->default(true)
                ->after('self_approval_threshold');

            $table->unsignedTinyInteger('min_approvers_required')
                ->default(1)
                ->after('enforce_separation_of_duties');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_settings', function (Blueprint $table) {
            $table->dropColumn([
                'business_size',
                'workflow_mode',
                'allow_self_approval',
                'self_approval_threshold',
                'enforce_separation_of_duties',
                'min_approvers_required',
            ]);
        });
    }
};

