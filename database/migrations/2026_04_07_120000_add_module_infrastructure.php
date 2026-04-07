<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Master module catalog
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Module-level permissions
        if (!Schema::hasTable('module_permissions')) {
            Schema::create('module_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->string('permission_key');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['module_id', 'permission_key']);
            });
        }

        // Pricing plan -> modules mapping
        if (!Schema::hasTable('plan_modules')) {
            Schema::create('plan_modules', function (Blueprint $table) {
                $table->id();
                // Align with existing SubscriptionPlan model/table
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->boolean('included')->default(true);
                $table->timestamps();
                $table->unique(['plan_id', 'module_id']);
            });
        }

        // Store-level effective module state (plan-sync or manual)
        if (!Schema::hasTable('store_modules')) {
            Schema::create('store_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->enum('status', ['enabled', 'disabled'])->default('enabled');
                $table->enum('source', ['plan', 'manual'])->default('plan');
                $table->timestamp('enabled_at')->nullable();
                $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['store_id', 'module_id']);
            });
        }

        // Store-specific overrides (force on/off, time-boxed)
        if (!Schema::hasTable('store_module_overrides')) {
            Schema::create('store_module_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->boolean('allow'); // true=force on, false=force off
                $table->text('reason')->nullable();
                $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['store_id', 'module_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_module_overrides');
        Schema::dropIfExists('store_modules');
        Schema::dropIfExists('plan_modules');
        Schema::dropIfExists('module_permissions');
        Schema::dropIfExists('modules');
    }
};
