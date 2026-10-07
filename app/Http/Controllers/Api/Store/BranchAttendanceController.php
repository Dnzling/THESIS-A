<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\Store\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: $user?->employee?->store_id);
        $branchId = (int) ($user?->branch_id ?: $user?->employee?->branch_id);
        if (!$storeId || !$branchId) {
            return response()->json(['message' => 'An assigned branch is required to view attendance.'], 422);
        }

        $branch = Branch::query()->where('store_id', $storeId)->findOrFail($branchId);
        $date = $validated['date'] ?? now()->toDateString();
        $employees = Employee::query()->with('user:id,fname,lname')
            ->where('store_id', $storeId)->where('branch_id', $branchId)
            ->where('status', 'active')
            ->when(!empty($validated['search']), function ($query) use ($validated) {
                $term = '%' . trim($validated['search']) . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('employee_number', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('fname', 'like', $term)
                            ->orWhere('lname', 'like', $term));
                });
            })
            ->orderBy('employee_number')->get(['id', 'user_id', 'employee_number']);
        $ids = $employees->pluck('id');

        $simple = Schema::hasTable('simple_employee_attendances')
            ? DB::table('simple_employee_attendances')->where('store_id', $storeId)
                ->whereIn('employee_id', $ids)->whereDate('attendance_date', $date)
                ->get()->keyBy('employee_id')
            : collect();
        $hr = Schema::hasTable('attendances')
            ? Attendance::query()->whereIn('employee_id', $ids)->whereDate('attendance_date', $date)
                ->latest('id')->get()->unique('employee_id')->keyBy('employee_id')
            : collect();

        $rows = $employees->map(function (Employee $employee) use ($simple, $hr, $date) {
            $simpleRecord = $simple->get($employee->id);
            $hrRecord = $hr->get($employee->id);
            $record = $hrRecord ?: $simpleRecord;
            $clockIn = $hrRecord?->clock_in ?? $simpleRecord?->clock_in_at;
            $clockOut = $hrRecord?->clock_out ?? $simpleRecord?->clock_out_at;
            $status = $record?->status ?: ($date === now()->toDateString() ? 'not_clocked_in' : 'absent');
            if ($clockIn && !$clockOut) $status = 'clocked_in';
            if (!$record && $date === now()->toDateString()) $status = 'not_clocked_in';
            return [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')),
                'status' => $status,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'late_minutes' => (int) ($record?->late_minutes ?? 0),
                'overtime_minutes' => (int) ($record?->overtime_minutes ?? 0),
                'worked_minutes' => $hrRecord?->total_worked_minutes ?? $simpleRecord?->worked_minutes,
            ];
        })->values();

        return response()->json(['data' => [
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
            'date' => $date,
            'summary' => [
                'total' => $rows->count(),
                'clocked_in' => $rows->where('status', 'clocked_in')->count(),
                'present' => $rows->filter(fn ($row) => in_array($row['status'], ['present', 'late'], true))->count(),
                'late' => $rows->filter(fn ($row) => $row['late_minutes'] > 0 || $row['status'] === 'late')->count(),
                'not_clocked_in' => $rows->where('status', 'not_clocked_in')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
            ],
            'employees' => $rows,
        ]]);
    }
}
