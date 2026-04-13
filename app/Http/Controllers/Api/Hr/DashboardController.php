<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Attendance;
use App\Models\Hr\Leave;
use App\Models\Hr\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getAnalyticsOverview(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        if ($endDate->lt($startDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date range. End date must be on or after start date.',
            ], 422);
        }

        $employeesBase = Employee::query()->where('store_id', $storeId);
        if ($branchId) {
            $employeesBase->where('branch_id', $branchId);
        }

        $totalEmployees = (clone $employeesBase)->count();
        $activeEmployees = (clone $employeesBase)->where('status', 'active')->count();
        $newHires = (clone $employeesBase)->whereBetween('hire_date', [$startDate->toDateString(), $endDate->toDateString()])->count();
        $attrition = (clone $employeesBase)
            ->whereIn('status', ['resigned', 'terminated', 'inactive'])
            ->whereBetween('termination_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        // Count distinct employees scheduled in the period (avoid double-counting multiple shifts)
        $scheduledCount = ShiftSchedule::query()
            ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->distinct('employee_id')
            ->count('employee_id');

        // Aggregate attendance by status (use DATE on attendance_date if datetime stored)
        $attendanceStats = Attendance::query()
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $presentCount = (int) ($attendanceStats['present'] ?? 0);
        $lateCount = (int) ($attendanceStats['late'] ?? 0);
        $absentCount = (int) ($attendanceStats['absent'] ?? 0);
        $leaveCount = (int) ($attendanceStats['on_leave'] ?? 0);

        $leavePending = Leave::query()
            ->where('status', 'pending')
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

        $leaveApproved = Leave::query()
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

        $overtimePending = OvertimeRequest::query()
            ->where('status', 'pending')
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

        $overtimeApprovedMinutes = (int) OvertimeRequest::query()
            ->where('status', 'approved')
            ->whereBetween('ot_start', [$startDate, $endDate])
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->sum('ot_minutes');

        $departmentDistribution = Employee::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unassigned') as name, COUNT(*) as total")
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(department), ''), 'Unassigned')"))
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total' => (int) $row->total,
            ])
            ->values();

        $employmentDistribution = Employee::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("COALESCE(NULLIF(TRIM(employment_type), ''), 'unspecified') as name, COUNT(*) as total")
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(employment_type), ''), 'unspecified')"))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
            ])
            ->values();

        $topAbsences = Attendance::query()
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', 'absent')
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->selectRaw('employee_id, COUNT(*) as total')
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->with('employee:id,fname,lname,department')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->employee_id,
                'name' => trim(($row->employee?->fname ?? '') . ' ' . ($row->employee?->lname ?? '')),
                'department' => $row->employee?->department,
                'total_absent_days' => (int) $row->total,
            ])
            ->values();

        $attendanceRate = $scheduledCount > 0
            ? round((($presentCount + $lateCount) / $scheduledCount) * 100, 2)
            : 0;

        $lateRate = $scheduledCount > 0
            ? round(($lateCount / $scheduledCount) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'branch_id' => $branchId,
                ],
                'workforce' => [
                    'total_employees' => $totalEmployees,
                    'active_employees' => $activeEmployees,
                    'new_hires' => $newHires,
                    'attrition_count' => $attrition,
                ],
                'attendance' => [
                    'scheduled' => $scheduledCount,
                    'present' => $presentCount,
                    'late' => $lateCount,
                    'absent' => $absentCount,
                    'on_leave' => $leaveCount,
                    'attendance_rate' => $attendanceRate,
                    'late_rate' => $lateRate,
                ],
                'leave_overtime' => [
                    'pending_leave_requests' => $leavePending,
                    'approved_leave_requests' => $leaveApproved,
                    'pending_overtime_requests' => $overtimePending,
                    'approved_overtime_minutes' => $overtimeApprovedMinutes,
                ],
                'breakdowns' => [
                    'department_distribution' => $departmentDistribution,
                    'employment_distribution' => $employmentDistribution,
                    'top_absences' => $topAbsences,
                ],
            ],
        ]);
    }

    public function getTodayStats(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        
        $today = now()->format('Y-m-d');
        
        $stats = [
            'date' => $today,
            'day_name' => now()->format('l'),
            'total_employees' => Employee::where('store_id', $storeId)->count(),
            // scheduled_today counts distinct scheduled employees for the day
            'scheduled_today' => ShiftSchedule::where('schedule_date', $today)
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->distinct('employee_id')
                ->count('employee_id'),
            // attended_today only counts attendances for employees who are scheduled today
            'attended_today' => Attendance::where('attendance_date', $today)
                ->whereIn('status', ['present', 'late'])
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->whereExists(function ($query) use ($today) {
                    $query->select(DB::raw(1))
                        ->from('shift_schedules')
                        ->whereColumn('shift_schedules.employee_id', 'attendances.employee_id')
                        ->where('shift_schedules.schedule_date', $today);
                })
                ->count(),
            'absent_today' => Attendance::where('attendance_date', $today)
                ->where('status', 'absent')
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'on_leave_today' => Attendance::where('attendance_date', $today)
                ->where('status', 'on_leave')
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'pending_leave_requests' => Leave::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'pending_overtime' => OvertimeRequest::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
        ];
        
        $stats['attendance_rate'] = $stats['scheduled_today'] > 0 
            ? round(($stats['attended_today'] / $stats['scheduled_today']) * 100, 2) 
            : 0;
            
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getWeeklyAttendance(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        
        // Aggregate by date (use DATE() to normalize datetime values) and filter by DATE(...) to avoid datetime mismatches
        $attendance = Attendance::whereHas('employee', fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('DATE(attendance_date) as attendance_date, status, count(*) as total')
            ->whereBetween(DB::raw('DATE(attendance_date)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy(DB::raw('DATE(attendance_date)'), 'status')
            ->get()
            ->groupBy('attendance_date');

        $data = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dayData = [
                'date' => $dateStr,
                'day' => $date->format('l'),
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'leave' => 0
            ];
            
            if (isset($attendance[$dateStr])) {
                foreach ($attendance[$dateStr] as $record) {
                    if ($record->status === 'present' || $record->status === 'late') {
                        $dayData['present'] += $record->total;
                    } elseif ($record->status === 'absent') {
                        $dayData['absent'] += $record->total;
                    } elseif ($record->status === 'on_leave') {
                        $dayData['leave'] += $record->total;
                    }
                }
            }
            
            $data[] = $dayData;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getMonthlySummary(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        $summary = [
            'month' => $month,
            'year' => $year,
            'total_employees' => Employee::where('store_id', $storeId)->count(),
            'total_scheduled' => ShiftSchedule::whereBetween('schedule_date', [$startDate, $endDate])
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'total_attendance' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'total_leaves' => Leave::where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'total_overtime' => OvertimeRequest::whereBetween('ot_start', [$startDate, $endDate])
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}