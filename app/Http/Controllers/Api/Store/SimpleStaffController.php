<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\Store\Branch;
use App\Services\Core\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SimpleStaffController extends Controller
{
    public function employees(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $employees = Employee::query()
            ->with(['user:id,fname,lname,email,role_id,branch_id,avatar_path', 'user.role:id,name,display_name', 'branch:id,name'])
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')),
                'avatar_url' => $employee->user?->avatar_url,
                'email' => $employee->user?->email,
                'role' => $employee->user?->role?->display_name ?? $employee->user?->role?->name,
                'branch' => $employee->branch?->name,
                'salary' => (float) ($employee->salary ?? 0),
                'pay_type' => $employee->pay_type ?? 'monthly',
                'status' => $employee->status,
                'hire_date' => $employee->hire_date,
            ]);

        return response()->json(['data' => $employees]);
    }

    public function attendances(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $storeId = (int) $request->user()->store_id;
        $simpleRows = Schema::hasTable('simple_employee_attendances')
            ? DB::table('simple_employee_attendances as attendance')
            ->join('employees as employee', 'employee.id', '=', 'attendance.employee_id')
            ->join('users as staff_user', 'staff_user.id', '=', 'employee.user_id')
            ->where('attendance.store_id', $storeId)
            ->where('employee.store_id', $storeId)
            ->when(isset($validated['date']), fn ($query) => $query->whereDate('attendance.attendance_date', $validated['date']))
            ->when(isset($validated['start_date']), fn ($query) => $query->whereDate('attendance.attendance_date', '>=', $validated['start_date']))
            ->when(isset($validated['end_date']), fn ($query) => $query->whereDate('attendance.attendance_date', '<=', $validated['end_date']))
            ->when(isset($validated['search']), function ($query) use ($validated) {
                $search = '%' . $validated['search'] . '%';
                $query->where(function ($scoped) use ($search) {
                    $scoped->where('staff_user.fname', 'like', $search)
                        ->orWhere('staff_user.lname', 'like', $search)
                        ->orWhere('employee.employee_number', 'like', $search);
                });
            })
            ->select('attendance.id', 'attendance.employee_id', 'attendance.attendance_date', 'attendance.status', 'attendance.note', 'attendance.clock_in_at', 'attendance.clock_out_at', 'attendance.worked_minutes', 'attendance.late_minutes', 'attendance.overtime_minutes', 'staff_user.fname', 'staff_user.lname', 'employee.employee_number')
            ->orderByDesc('attendance.attendance_date')
            ->orderBy('staff_user.fname')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'attendance_date' => (string) $row->attendance_date,
                'status' => $row->status,
                'note' => $row->note,
                'clock_in_at' => $row->clock_in_at,
                'clock_out_at' => $row->clock_out_at,
                'worked_minutes' => $row->worked_minutes,
                'late_minutes' => $row->late_minutes,
                'overtime_minutes' => $row->overtime_minutes,
                'fname' => $row->fname,
                'lname' => $row->lname,
                'employee_number' => $row->employee_number,
            ])
            : collect();

        $hrRows = Schema::hasTable('attendances')
            ? Attendance::query()
                ->with('employee.user')
                ->whereHas('employee', function ($query) use ($storeId, $validated) {
                    $query->where('store_id', $storeId)
                        ->when(isset($validated['search']), function ($employeeQuery) use ($validated) {
                            $term = '%' . $validated['search'] . '%';
                            $employeeQuery->where(function ($matches) use ($term) {
                                $matches->where('employee_number', 'like', $term)
                                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                                        ->where('fname', 'like', $term)
                                        ->orWhere('lname', 'like', $term));
                            });
                        });
                })
                ->when(isset($validated['date']), fn ($query) => $query->whereDate('attendance_date', $validated['date']))
                ->when(isset($validated['start_date']), fn ($query) => $query->whereDate('attendance_date', '>=', $validated['start_date']))
                ->when(isset($validated['end_date']), fn ($query) => $query->whereDate('attendance_date', '<=', $validated['end_date']))
                ->orderByDesc('attendance_date')
                ->get()
                ->map(fn (Attendance $row) => [
                    'id' => $row->id,
                    'employee_id' => $row->employee_id,
                    'attendance_date' => $row->attendance_date?->toDateString(),
                    'status' => $row->status,
                    'note' => $row->notes,
                    'clock_in_at' => $row->clock_in,
                    'clock_out_at' => $row->clock_out,
                    'worked_minutes' => $row->total_worked_minutes,
                    'late_minutes' => $row->late_minutes,
                    'overtime_minutes' => $row->overtime_minutes,
                    'fname' => $row->employee?->user?->fname ?? $row->employee?->fname,
                    'lname' => $row->employee?->user?->lname ?? $row->employee?->lname,
                    'employee_number' => $row->employee?->employee_number,
                ])
            : collect();

        $rows = $simpleRows->concat($hrRows)
            ->groupBy(fn (array $row) => $row['employee_id'] . '|' . $row['attendance_date'])
            ->map(fn ($records) => $records->sortByDesc(fn (array $row) => $row['clock_in_at'] ?: '')->first())
            ->sortByDesc('attendance_date')
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function myClockStatus(Request $request): JsonResponse
    {
        $employee = $this->simpleEmployeeForUser($request);
        $geofenceRequired = (bool) $this->attendanceBranch($employee, $request)?->geofence_enabled;
        $row = DB::table('simple_employee_attendances')
            ->where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->orderByDesc('id')
            ->first()
            ?? DB::table('simple_employee_attendances')
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', now()->toDateString())
                ->first();

        $overtimeNow = $row && $row->clock_in_at && !$row->clock_out_at && $row->scheduled_close_at
            ? max(0, (int) Carbon::parse($row->scheduled_close_at)->diffInMinutes(now(), false)) : 0;

        return response()->json(['data' => ['attendance' => $row, 'overtime_minutes_now' => $overtimeNow, 'geofence_required' => $geofenceRequired]]);
    }

    public function stageClock(Request $request): JsonResponse
    {
        $employee = $this->simpleEmployeeForUser($request);
        $coordinates = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $branch = $this->attendanceBranch($employee, $request);
        if ($branch?->geofence_enabled) {
            abort_unless($branch->latitude !== null && $branch->longitude !== null, 422, 'Branch geofence location is not configured.');
            abort_unless(isset($coordinates['latitude'], $coordinates['longitude']), 422, 'Allow location access to clock in or out.');
            $earthRadius = 6371000;
            $latitude = (float) $coordinates['latitude'];
            $longitude = (float) $coordinates['longitude'];
            $targetLat = (float) $branch->latitude;
            $targetLng = (float) $branch->longitude;
            $latDistance = deg2rad($targetLat - $latitude);
            $lngDistance = deg2rad($targetLng - $longitude);
            $a = sin($latDistance / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($targetLat)) * sin($lngDistance / 2) ** 2;
            $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
            abort_unless($distance <= (int) ($branch->geofence_radius_m ?? 5), 403, 'You are outside the allowed attendance radius.');
        }
        $store = $request->user()->store;
        $hours = is_array($store?->settings) ? ($store->settings['simple_operating_hours'] ?? null) : null;
        $now = now();

        $result = DB::transaction(function () use ($employee, $request, $hours, $now) {
            $open = DB::table('simple_employee_attendances')
                ->where('employee_id', $employee->id)
                ->whereNotNull('clock_in_at')
                ->whereNull('clock_out_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($open) {
                $clockIn = Carbon::parse($open->clock_in_at);
                $workedMinutes = max(0, (int) $clockIn->diffInMinutes($now, false));
                $overtimeMinutes = $open->scheduled_close_at
                    ? max(0, (int) Carbon::parse($open->scheduled_close_at)->diffInMinutes($now, false)) : 0;
                DB::table('simple_employee_attendances')->where('id', $open->id)->update([
                    'clock_out_at' => $now,
                    'worked_minutes' => $workedMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'updated_at' => $now,
                ]);
                return ['action' => 'clock_out', 'worked_minutes' => $workedMinutes, 'overtime_minutes' => $overtimeMinutes];
            }

            abort_unless(is_array($hours) && !empty($hours['days']) && !empty($hours['opens_at']) && !empty($hours['closes_at']), 422, 'Store operating hours have not been configured.');
            abort_unless(in_array(strtolower($now->format('l')), $hours['days'], true), 422, 'The store is not scheduled to operate today.');
            $opening = Carbon::parse($now->toDateString() . ' ' . $hours['opens_at']);
            $closing = Carbon::parse($now->toDateString() . ' ' . $hours['closes_at']);
            abort_unless($closing->greaterThan($opening), 422, 'Store operating hours are invalid.');
            $existing = DB::table('simple_employee_attendances')
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();
            abort_unless(!$existing, 422, 'Attendance is already recorded for today. Ask the owner to correct it.');

            $lateMinutes = max(0, (int) $opening->diffInMinutes($now, false));
            DB::table('simple_employee_attendances')->insert([
                'store_id' => $employee->store_id,
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'status' => 'present',
                'clock_in_at' => $now,
                'scheduled_close_at' => $closing,
                'late_minutes' => $lateMinutes,
                'recorded_by' => $request->user()->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return ['action' => 'clock_in', 'late_minutes' => $lateMinutes];
        });

        return response()->json(['data' => $result, 'message' => $result['action'] === 'clock_in' ? 'Clocked in.' : 'Clocked out.']);
    }

    private function simpleEmployeeForUser(Request $request): Employee
    {
        $store = $request->user()->store;
        abort_unless(in_array($store?->subscription_tier, ['free', 'simple'], true), 403);

        return Employee::query()
            ->where('store_id', $request->user()->store_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function attendanceBranch(Employee $employee, Request $request): ?Branch
    {
        $branches = Branch::query()->where('store_id', $employee->store_id);
        $branchId = $request->user()->branch_id ?: $employee->branch_id;
        if ($branchId && ($branch = (clone $branches)->find($branchId))) {
            return $branch;
        }

        return $branches->orderByDesc('is_main_branch')->orderBy('id')->first();
    }

    public function showEmployee(Request $request, string $employeeId): JsonResponse
    {
        abort_unless(ctype_digit($employeeId), 404);
        $employee = Employee::query()
            ->where('store_id', $request->user()->store_id)
            ->with(['user.role', 'branch'])
            ->findOrFail((int) $employeeId);

        return response()->json(['data' => $this->employeeDetails($employee)]);
    }

    public function updateEmployee(Request $request, string $employeeId): JsonResponse
    {
        abort_unless(ctype_digit($employeeId), 404);
        $storeId = (int) $request->user()->store_id;
        $employee = Employee::query()->where('store_id', $storeId)->with('user')->findOrFail((int) $employeeId);
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'regex:/^09\d{2}-\d{3}-\d{4}$/'],
            'pay_type' => ['required', Rule::in(['monthly', 'daily'])],
            'salary' => ['required', 'numeric', 'gt:0'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(function ($query) use ($storeId) {
                $query->where(function ($scoped) use ($storeId) {
                    $scoped->whereNull('store_id')->orWhere('store_id', $storeId);
                });
            })],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'hire_date' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'contract_end_date' => ['nullable', 'required_if:employment_type,contract', 'date', 'after_or_equal:hire_date'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'suspended'])],
        ]);

        $roleChanged = (int) $employee->role_id !== (int) $validated['role_id'];
        DB::transaction(function () use ($employee, $validated) {
            $employee->user->fill([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'phone_number' => $validated['phone_number'] ?? null,
                'role_id' => $validated['role_id'],
                'branch_id' => $validated['branch_id'],
            ])->save();
            $employee->fill([
                'role_id' => $validated['role_id'],
                'branch_id' => $validated['branch_id'],
                'hire_date' => $validated['hire_date'],
                'employment_type' => $validated['employment_type'],
                'contract_end_date' => $validated['employment_type'] === 'contract' ? $validated['contract_end_date'] : null,
                'pay_type' => $validated['pay_type'],
                'salary' => $validated['salary'],
                'status' => $validated['status'],
            ])->save();
        });

        if ($roleChanged) {
            app(PermissionService::class)->clearUserCache($employee->user);
        }

        return response()->json(['data' => $this->employeeDetails($employee->fresh(['user.role', 'branch'])), 'message' => 'Employee updated.']);
    }

    private function employeeDetails(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
                'pay_type' => $employee->pay_type ?? 'monthly',
                'salary' => (float) ($employee->salary ?? 0),
            'fname' => $employee->user?->fname,
            'lname' => $employee->user?->lname,
            'email' => $employee->user?->email,
            'phone_number' => $employee->user?->phone_number,
            'avatar_url' => $employee->user?->avatar_url,
            'role_id' => $employee->role_id,
            'role' => $employee->user?->role?->display_name ?? $employee->user?->role?->name,
            'branch_id' => $employee->branch_id,
            'branch' => $employee->branch?->name,
            'hire_date' => $employee->hire_date,
            'employment_type' => $employee->employment_type,
            'contract_end_date' => $employee->contract_end_date?->toDateString(),
            'resignation_date' => $employee->resignation_date?->toDateString(),
            'last_working_day' => $employee->last_working_day?->toDateString(),
            'status' => $employee->status,
        ];
    }

    public function payments(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $rows = DB::table('simple_employee_payments as payment')
            ->join('employees as employee', 'employee.id', '=', 'payment.employee_id')
            ->join('users as staff_user', 'staff_user.id', '=', 'employee.user_id')
            ->where('payment.store_id', $storeId)
            ->where('employee.store_id', $storeId)
            ->select('payment.id', 'payment.employee_id', 'payment.payment_date', 'payment.amount', 'payment.note', 'staff_user.fname', 'staff_user.lname', 'employee.employee_number')
            ->orderByDesc('payment.payment_date')
            ->orderByDesc('payment.id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function savePayment(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('store_id', $storeId)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $id = DB::table('simple_employee_payments')->insertGetId([
            ...$validated,
            'store_id' => $storeId,
            'recorded_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $id], 'message' => 'Payment record saved.'], 201);
    }
}
