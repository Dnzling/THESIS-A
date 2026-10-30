<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->decimal('benefit_limit', 12, 2)->nullable();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->foreignId('deduction_type_id')->nullable()->constrained('deduction_types')->nullOnDelete();
        });

        Schema::create('employee_benefit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('deduction_type_id')->constrained('deduction_types');
            $table->string('provider', 150);
            $table->string('service_type', 120);
            $table->date('service_date');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('used_amount', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'settled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('request_attachment_path')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'status']);
            $table->index(['employee_id', 'deduction_type_id']);
        });

        DB::table('navigation_items')->updateOrInsert(
            ['name' => 'hr.benefit-requests'],
            ['display_name' => 'Benefit Requests', 'module' => 'hr', 'section' => 'benefits',
                'route_name' => 'hr.benefit-requests', 'route_path' => '/hr/benefit-requests',
                'icon' => 'pi pi-heart', 'display_order' => 14, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now()]
        );
        $navigationId = DB::table('navigation_items')->where('name', 'hr.benefit-requests')->value('id');
        $permissionId = DB::table('permissions')->where('name', 'hr.settings.manage')->value('id');
        if ($navigationId && $permissionId) {
            DB::table('navigation_permissions')->updateOrInsert(
                ['navigation_item_id' => $navigationId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $navigationId = DB::table('navigation_items')->where('name', 'hr.benefit-requests')->value('id');
        if ($navigationId) {
            DB::table('navigation_permissions')->where('navigation_item_id', $navigationId)->delete();
            DB::table('navigation_items')->where('id', $navigationId)->delete();
        }
        Schema::dropIfExists('employee_benefit_requests');
        Schema::table('payroll_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('deduction_type_id'));
        Schema::table('deduction_types', fn (Blueprint $table) => $table->dropColumn('benefit_limit'));
    }
};
