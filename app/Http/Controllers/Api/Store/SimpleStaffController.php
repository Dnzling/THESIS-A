<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Services\Core\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimpleStaffController extends Controller
{
    public function employees(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $employees = Employee::query()
            ->with(['user:id,fname,lname,email,role_id,branch_id', 'user.role:id,name,display_name', 'branch:id,name'])
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')),
                'email' => $employee->user?->email,
                'role' => $employee->user?->role?->display_name ?? $employee->user?->role?->name,
                'branch' => $employee->branch?->name,
                'status' => $employee->status,
                'hire_date' => $employee->hire_date,
            ]);

        return response()->json(['data' => $employees]);
    }

    public function attendances(Request $request): JsonResponse
    {
        $validated = $request->validate(['date' => ['nullable', 'date']]);
        $date = $validated['date'] ?? now()->toDateString();
        $rows = DB::table('simple_employee_attendances as attendance')
            ->join('employees as employee', 'employee.id', '=', 'attendance.employee_id')
            ->where('attendance.store_id', $request->user()->store_id)
            ->where('employee.store_id', $request->user()->store_id)
            ->whereDate('attendance.attendance_date', $date)
            ->select('attendance.id', 'attendance.employee_id', 'attendance.attendance_date', 'attendance.status', 'attendance.note')
            ->get();

        return response()->json(['data' => $rows]);
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
            'phone_number' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(function ($query) use ($storeId) {
                $query->where(function ($scoped) use ($storeId) {
                    $scoped->whereNull('store_id')->orWhere('store_id', $storeId);
                });
            })],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'hire_date' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
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
            'status' => $employee->status,
        ];
    }

    public function saveAttendance(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('store_id', $storeId)],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['present', 'absent', 'day_off'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('simple_employee_attendances')->updateOrInsert(
            ['employee_id' => $validated['employee_id'], 'attendance_date' => $validated['attendance_date']],
            [
                'store_id' => $storeId,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'recorded_by' => $request->user()->id,
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Attendance saved.']);
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
