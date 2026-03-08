<?php
// database/migrations/2024_01_01_000000_create_attendance_system_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SHIFTS TABLE (Core shift definitions)
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->decimal('total_hours', 5, 2)->default(8.00);
            $table->enum('shift_type', ['fixed', 'rotating', 'flexible'])->default('fixed');
            $table->json('week_days')->nullable(); // ['monday','tuesday',...] for fixed shifts
            $table->integer('grace_period_minutes')->default(15);
            $table->boolean('has_night_diff')->default(false);
            $table->decimal('night_diff_rate', 3, 2)->default(1.10);
            $table->integer('min_employees_required')->default(1);
            $table->string('color', 20)->default('#3b82f6');
            $table->boolean('is_active')->default(true);
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. SCHEDULE TEMPLATES (Recurring weekly patterns)
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->json('pattern'); // Weekly pattern configuration
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. SHIFT ASSIGNMENTS (Permanent/Temporary shift assignments)
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('schedule_templates')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('assignment_type', ['permanent', 'temporary', 'cover'])->default('permanent');
            $table->foreignId('cover_for')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('recurring_pattern')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['employee_id', 'start_date', 'end_date']);
        });

        // 4. SHIFT SCHEDULES (Generated daily schedules)
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('shift_assignments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('schedule_templates')->nullOnDelete();
            $table->date('schedule_date');
            $table->enum('generation_method', ['manual', 'auto_template', 'bulk'])->default('manual');
            $table->enum('status', ['scheduled', 'cancelled', 'completed'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['employee_id', 'schedule_date']);
            $table->index(['schedule_date', 'status']);
        });

        // 5. EMPLOYEE AVAILABILITY (When employees can work)
        Schema::create('employee_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->boolean('is_available')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'day_of_week', 'effective_from'], 'emp_avail_unique');
        });

        // 6. HOLIDAY SCHEDULES
        Schema::create('holiday_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date');
            $table->enum('holiday_type', ['regular', 'special', 'company'])->default('regular');
            $table->decimal('rate_multiplier', 3, 2)->default(2.0);
            $table->boolean('is_working_holiday')->default(false);
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['holiday_date', 'store_id']);
        });

        // 7. ATTENDANCE TABLE (Main attendance records)
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('shift_schedules')->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->enum('clock_in_method', ['biometric', 'mobile', 'web', 'manual'])->nullable();
            $table->enum('clock_out_method', ['biometric', 'mobile', 'web', 'manual'])->nullable();
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            $table->string('clock_in_location')->nullable();
            $table->string('clock_out_location')->nullable();

            // Break times
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->integer('break_minutes')->default(0);
            
            // Calculations
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('night_differential_minutes')->default(0);
            $table->integer('total_worked_minutes')->default(0);
            
            // Status and flags
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'])->default('absent');
            $table->boolean('is_ot_approved')->default(false);
            $table->boolean('is_restday_work')->default(false);
            
            // Approvals
            $table->foreignId('ot_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ot_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['clock_in', 'clock_out']);
        });

        // 8. OVERTIME REQUESTS
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->dateTime('ot_start');
            $table->dateTime('ot_end');
            $table->integer('ot_minutes');
            $table->enum('ot_type', ['regular', 'holiday', 'rest_day'])->default('regular');
            $table->decimal('rate_multiplier', 3, 2)->default(1.25);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'status']);
            $table->index(['ot_start', 'ot_end']);
        });

        // 9. SHIFT SWAP REQUESTS
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requestor_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requestor_schedule_id')->constrained('shift_schedules')->cascadeOnDelete();
            $table->foreignId('receiver_schedule_id')->constrained('shift_schedules')->cascadeOnDelete();
            $table->enum('swap_type', ['full_swap', 'give_away', 'pick_up'])->default('full_swap');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['requestor_id', 'status']);
            $table->index(['receiver_id', 'status']);
        });

        // 10. LEAVES TABLE
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('leave_type', ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'bereavement', 'others']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('deduct_from_balance')->default(true);
            $table->json('handover_notes')->nullable();
            $table->foreignId('handover_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // 11. LEAVE BALANCES TABLE
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->enum('leave_type', ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'bereavement', 'others'])->default('vacation');
            
            // Balance Information
            $table->decimal('yearly_quota', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('pending_days', 8, 2)->default(0);
            $table->decimal('remaining_days', 8, 2)->default(0);
            $table->decimal('carried_over', 8, 2)->default(0);
            $table->decimal('expired_days', 8, 2)->default(0);
            
            // Period Information
            $table->year('year')->index();
            $table->date('expiry_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'expired', 'carried_over', 'frozen', 'archived'])->default('active');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            
            $table->index(['employee_id', 'year', 'leave_type']);
            $table->index(['status', 'expiry_date']);
            $table->unique(['employee_id', 'year', 'leave_type'], 'unique_employee_leave_balance');
        });
    }

    public function down(): void
    {
        // Drop in reverse order to avoid foreign key constraints
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('holiday_schedules');
        Schema::dropIfExists('employee_availability');
        Schema::dropIfExists('shift_schedules');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('schedule_templates');
        Schema::dropIfExists('shifts');
    }
};