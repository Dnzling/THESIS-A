<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Store35August2026Seeder extends Seeder
{
    private const STORE_ID = 35;

    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            $store = DB::table('stores')->where('id', self::STORE_ID)->first();
            if (!$store) {
                $this->command?->error('Store id 35 does not exist. Seed the store first.');
                return;
            }

            $branchId = $this->branch($now);
            $roleId = $this->role($now);
            $userIds = $this->users($branchId, $roleId, $now);
            $employees = $this->employees($branchId, $roleId, $userIds, $now);
            $shifts = $this->shifts($now);
            $this->products($now);
            $this->holidays($now);
            $this->schedulesAndAttendance($employees, $shifts, $userIds[0], $now);
            $this->leaveAndBalances($employees, $userIds[0], $now);
            $this->overtime($employees, $userIds[0], $now);
            $this->swapRequest($employees, $userIds[0], $now);
        });

        $this->command?->info('Store 35 August 2026 demo data seeded.');
    }

    private function role(Carbon $now): int
    {
        DB::table('roles')->updateOrInsert(['name' => 'store_demo_staff'], [
            'display_name' => 'Store Demo Staff', 'code' => 'DEMO', 'description' => 'Demo data role',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) DB::table('roles')->where('name', 'store_demo_staff')->value('id');
    }

    private function branch(Carbon $now): int
    {
        DB::table('branches')->updateOrInsert(['store_id' => self::STORE_ID, 'branch_code' => 'STORE35-MAIN'], [
            'name' => 'Store 35 Main Branch', 'address' => 'Demo showroom', 'city' => 'Manila', 'province' => 'Metro Manila',
            'contact_number' => '09170000035', 'is_main_branch' => true, 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int) DB::table('branches')->where('branch_code', 'STORE35-MAIN')->value('id');
    }

    private function users(int $branchId, int $roleId, Carbon $now): array
    {
        $ids = [];
        foreach ([['Ana', 'Santos'], ['Ben', 'Reyes'], ['Carla', 'Cruz'], ['Diego', 'Garcia'], ['Ella', 'Flores'], ['Frank', 'Mendoza']] as $i => [$first, $last]) {
            $email = 'store35.demo.' . ($i + 1) . '@fsp.local';
            DB::table('users')->updateOrInsert(['email' => $email], [
                'fname' => $first, 'lname' => $last, 'store_id' => self::STORE_ID, 'branch_id' => $branchId,
                'password' => Hash::make('password123'), 'email_verified_at' => $now, 'role_id' => $roleId,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $ids[] = (int) DB::table('users')->where('email', $email)->value('id');
        }
        return $ids;
    }

    private function employees(int $branchId, int $roleId, array $userIds, Carbon $now): array
    {
        $ids = [];
        foreach ($userIds as $i => $userId) {
            $number = 'S35-DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            DB::table('employees')->updateOrInsert(['store_id' => self::STORE_ID, 'employee_number' => $number], [
                'user_id' => $userId, 'branch_id' => $branchId,
                'city' => 'Manila', 'hire_date' => '2025-01-06', 'role_id' => $roleId, 'department' => ['Sales', 'Inventory', 'Logistics', 'Finance', 'HR', 'Store Operations'][$i],
                'employment_type' => 'full_time', 'salary' => 28000 + ($i * 1500), 'status' => 'active', 'deleted_at' => null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $ids[] = (int) DB::table('employees')->where('store_id', self::STORE_ID)->where('employee_number', $number)->value('id');
        }
        return $ids;
    }

    private function shifts(Carbon $now): array
    {
        $result = [];
        foreach ([['S35-MORNING', 'Morning Showroom', '08:00:00', '17:00:00', '#2563eb'], ['S35-AFTERNOON', 'Afternoon Showroom', '10:00:00', '19:00:00', '#f59e0b']] as [$code, $name, $start, $end, $color]) {
            DB::table('shifts')->updateOrInsert(['code' => $code], ['name' => $name, 'start_time' => $start, 'end_time' => $end, 'break_start' => '12:00:00', 'break_end' => '13:00:00', 'total_hours' => 8, 'shift_type' => 'fixed', 'week_days' => json_encode(['monday','tuesday','wednesday','thursday','friday','saturday']), 'color' => $color, 'is_active' => true, 'store_id' => self::STORE_ID, 'created_at' => $now, 'updated_at' => $now]);
            $result[] = (int) DB::table('shifts')->where('code', $code)->value('id');
        }
        return $result;
    }

    private function products(Carbon $now): void
    {
        foreach ([['S35-SOF-001', 'Luna 3-Seater Sofa', 'Sofas', 24999], ['S35-BED-001', 'Astra Queen Bed', 'Beds', 18999], ['S35-TBL-001', 'Mira Dining Table', 'Tables', 15999], ['S35-CHR-001', 'Naya Accent Chair', 'Chairs', 7499]] as [$sku, $name, $category, $price]) {
            DB::table('categories')->updateOrInsert(['store_id' => self::STORE_ID, 'category_code' => 'S35-' . strtoupper($category)], ['category_name' => $category, 'level' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            $categoryId = DB::table('categories')->where('store_id', self::STORE_ID)->where('category_code', 'S35-' . strtoupper($category))->value('id');
            DB::table('products')->updateOrInsert(['store_id' => self::STORE_ID, 'sku' => $sku], ['product_name' => $name, 'description' => 'Store 35 demo product', 'category_id' => $categoryId, 'brand' => 'FSP Demo', 'base_price' => $price, 'cost_price' => $price * .7, 'is_featured' => true, 'is_new_arrival' => true, 'is_active' => true, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null]);
        }
    }

    private function holidays(Carbon $now): void
    {
        $holidays = [['New Year\'s Day', '2026-01-01', 'regular', 2], ['EDSA People Power Revolution Anniversary', '2026-02-25', 'special', 1.3], ['Maundy Thursday', '2026-04-02', 'regular', 2], ['Good Friday', '2026-04-03', 'regular', 2], ['Black Saturday', '2026-04-04', 'special', 1.3], ['Araw ng Kagitingan', '2026-04-09', 'regular', 2], ['Labor Day', '2026-05-01', 'regular', 2], ['Independence Day', '2026-06-12', 'regular', 2], ['Ninoy Aquino Day', '2026-08-21', 'special', 1.3], ['National Heroes Day', '2026-08-31', 'regular', 2], ['All Saints\' Day', '2026-11-01', 'special', 1.3], ['All Souls\' Day', '2026-11-02', 'special', 1.3], ['Bonifacio Day', '2026-11-30', 'regular', 2], ['Feast of the Immaculate Conception', '2026-12-08', 'special', 1.3], ['Christmas Eve', '2026-12-24', 'special', 1.3], ['Christmas Day', '2026-12-25', 'regular', 2], ['Rizal Day', '2026-12-30', 'regular', 2], ['Last Day of the Year', '2026-12-31', 'special', 1.3]];
        foreach ($holidays as [$name, $date, $type, $rate]) DB::table('holiday_schedules')->updateOrInsert(['holiday_date' => $date, 'store_id' => self::STORE_ID], ['name' => $name, 'holiday_type' => $type, 'rate_multiplier' => $rate, 'is_working_holiday' => false, 'description' => 'Philippine holiday 2026', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function schedulesAndAttendance(array $employees, array $shifts, int $approver, Carbon $now): void
    {
        foreach ($employees as $i => $employeeId) foreach (CarbonPeriod::create('2026-08-01', '2026-08-31') as $date) {
            if ($date->isWeekend()) continue;
            $shiftId = $shifts[$i % 2]; $in = $date->copy()->setTime($i % 3 + 8, 0); $out = $date->copy()->setTime($i % 3 + 17, 0);
            DB::table('shift_schedules')->updateOrInsert(['employee_id' => $employeeId, 'schedule_date' => $date->toDateString()], ['shift_id' => $shiftId, 'generation_method' => 'bulk', 'status' => 'completed', 'assigned_by' => $approver, 'created_at' => $now, 'updated_at' => $now]);
            $scheduleId = DB::table('shift_schedules')->where('employee_id', $employeeId)->where('schedule_date', $date->toDateString())->value('id');
            DB::table('attendances')->updateOrInsert(['employee_id' => $employeeId, 'attendance_date' => $date->toDateString()], ['shift_id' => $shiftId, 'schedule_id' => $scheduleId, 'clock_in' => $in, 'clock_out' => $out, 'clock_in_method' => 'web', 'clock_out_method' => 'web', 'break_minutes' => 60, 'total_worked_minutes' => 480, 'status' => $i === 2 && $date->day === 12 ? 'late' : 'present', 'late_minutes' => $i === 2 && $date->day === 12 ? 18 : 0, 'approved_by' => $approver, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function leaveAndBalances(array $employees, int $approver, Carbon $now): void
    {
        foreach ($employees as $employeeId) foreach (['vacation' => 15, 'sick' => 10, 'personal' => 5] as $type => $quota) DB::table('leave_balances')->updateOrInsert(['employee_id' => $employeeId, 'year' => 2026, 'leave_type' => $type], ['store_id' => self::STORE_ID, 'yearly_quota' => $quota, 'used_days' => 0, 'pending_days' => 0, 'remaining_days' => $quota, 'status' => 'active', 'created_by' => $approver, 'updated_by' => $approver, 'created_at' => $now, 'updated_at' => $now]);
        $leave = [['vacation', 0, 2, 'Family vacation', 'approved'], ['sick', 1, 20, 'Medical rest', 'approved'], ['personal', 2, 27, 'Personal errand', 'pending']];
        foreach ($leave as [$type, $employeeIndex, $day, $reason, $status]) { $employeeId = $employees[$employeeIndex]; DB::table('leaves')->updateOrInsert(['employee_id' => $employeeId, 'start_date' => '2026-08-' . str_pad($day, 2, '0', STR_PAD_LEFT)], ['leave_type' => $type, 'end_date' => '2026-08-' . str_pad($day + 1, 2, '0', STR_PAD_LEFT), 'total_days' => 2, 'reason' => $reason, 'is_paid' => true, 'deduct_from_balance' => true, 'status' => $status, 'approved_by' => $status === 'approved' ? $approver : null, 'approved_at' => $status === 'approved' ? $now : null, 'created_at' => $now, 'updated_at' => $now]); }
    }

    private function overtime(array $employees, int $approver, Carbon $now): void
    {
        foreach ([[$employees[0], '2026-08-07', 'approved'], [$employees[1], '2026-08-14', 'pending']] as [$employeeId, $date, $status]) { $attendanceId = DB::table('attendances')->where('employee_id', $employeeId)->where('attendance_date', $date)->value('id'); DB::table('attendances')->where('id', $attendanceId)->update(['overtime_minutes' => 120, 'is_ot_approved' => $status === 'approved', 'ot_approved_by' => $status === 'approved' ? $approver : null, 'ot_approved_at' => $status === 'approved' ? $now : null]); DB::table('overtime_requests')->updateOrInsert(['employee_id' => $employeeId, 'attendance_id' => $attendanceId], ['ot_start' => $date . ' 17:00:00', 'ot_end' => $date . ' 19:00:00', 'ot_minutes' => 120, 'ot_type' => 'regular', 'rate_multiplier' => 1.25, 'reason' => 'August showroom inventory and customer delivery support', 'status' => $status, 'approved_by' => $status === 'approved' ? $approver : null, 'approved_at' => $status === 'approved' ? $now : null, 'created_at' => $now, 'updated_at' => $now]); }
    }

    private function swapRequest(array $employees, int $approver, Carbon $now): void
    {
        $a = DB::table('shift_schedules')->where('employee_id', $employees[0])->where('schedule_date', '2026-08-25')->value('id'); $b = DB::table('shift_schedules')->where('employee_id', $employees[1])->where('schedule_date', '2026-08-25')->value('id');
        DB::table('shift_swap_requests')->updateOrInsert(['requestor_id' => $employees[0], 'receiver_id' => $employees[1], 'requestor_schedule_id' => $a, 'receiver_schedule_id' => $b], ['swap_type' => 'full_swap', 'status' => 'pending', 'reason' => 'Personal schedule conflict', 'created_at' => $now, 'updated_at' => $now]);
    }
}
