<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Attendance;
use App\Models\Hr\Leave;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\ShiftSwapRequest;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getActionQueues(Request $request)
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        if ($storeId <= 0) {
            return response()->json(['success' => false, 'message' => 'Your account is not assigned to a store.'], 422);
        }

        $employeeInStore = fn ($query) => $query->where('store_id', $storeId);
        $leaves = Leave::query()->where('status', 'pending')->whereHas('employee', $employeeInStore);
        $overtime = OvertimeRequest::query()->where('status', 'pending')->whereHas('employee', $employeeInStore);
        $swaps = ShiftSwapRequest::query()->where('status', 'pending')->whereHas('requestor', $employeeInStore);
        $applications = JobApplication::query()->where('status', 'Applied')
            ->whereHas('jobPosting', fn ($query) => $query->where('store_id', $storeId));

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'active_employees' => Employee::where('store_id', $storeId)->where('status', 'active')->count(),
                    'pending_leaves' => (clone $leaves)->count(),
                    'pending_overtime' => (clone $overtime)->count(),
                    'pending_swaps' => (clone $swaps)->count(),
                    'new_applicants' => (clone $applications)->count(),
                ],
                'leaves' => (clone $leaves)->with('employee.user:id,fname,lname')
                    ->orderBy('start_date')->limit(5)->get()
                    ->map(fn ($leave) => [
                        'id' => $leave->id,
                        'employee' => trim(($leave->employee?->user?->fname ?? '') . ' ' . ($leave->employee?->user?->lname ?? '')),
                        'type' => $leave->leave_type,
                        'start_date' => $leave->start_date?->toDateString(),
                        'end_date' => $leave->end_date?->toDateString(),
                        'days' => $leave->total_days,
                    ]),
                'overtime' => (clone $overtime)->with('employee.user:id,fname,lname')
                    ->orderBy('ot_start')->limit(5)->get()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'employee_id' => $item->employee_id,
                        'employee' => trim(($item->employee?->user?->fname ?? '') . ' ' . ($item->employee?->user?->lname ?? '')),
                        'date' => $item->ot_start?->toDateString(),
                        'minutes' => (int) $item->ot_minutes,
                        'reason' => $item->reason,
                    ]),
                'swaps' => (clone $swaps)->with(['requestor.user:id,fname,lname', 'receiver.user:id,fname,lname', 'requestorSchedule:id,schedule_date'])
                    ->oldest('created_at')->limit(5)->get()
                    ->map(fn ($swap) => [
                        'id' => $swap->id,
                        'requestor' => trim(($swap->requestor?->user?->fname ?? '') . ' ' . ($swap->requestor?->user?->lname ?? '')),
                        'receiver' => trim(($swap->receiver?->user?->fname ?? '') . ' ' . ($swap->receiver?->user?->lname ?? '')),
                        'date' => $swap->requestorSchedule?->schedule_date?->toDateString(),
                        'type' => $swap->swap_type,
                    ]),
                'applications' => (clone $applications)->with('jobPosting:id,title')
                    ->oldest('application_date')->limit(5)->get()
                    ->map(fn ($application) => [
                        'id' => $application->id,
                        'posting_id' => $application->job_posting_id,
                        'name' => $application->full_name,
                        'position' => $application->jobPosting?->title,
                        'date' => $application->application_date?->toDateString(),
                    ]),
            ],
        ]);
    }

    public function getAnalyticsOverview(Request $request)
    {
        $user = $request->user();
        $storeId = (int) ($user?->store_id ?: DB::table('employees')->where('user_id', $user?->id)->value('store_id'));
        if ($storeId <= 0) {
            return response()->json(['success' => false, 'message' => 'Your account is not assigned to a store.'], 422);
        }

        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        $branchId = !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $assignedBranchId = (int) ($user?->branch_id ?: DB::table('employees')->where('user_id', $user?->id)->value('branch_id'));
        if ($assignedBranchId > 0) {
            if ($branchId && $branchId !== $assignedBranchId) {
                return response()->json(['success' => false, 'message' => 'You can only view analytics for your assigned branch.'], 403);
            }
            $branchId = $assignedBranchId;
        }
        if ($branchId && !DB::table('branches')->where('store_id', $storeId)->where('id', $branchId)->exists()) {
            return response()->json(['success' => false, 'message' => 'The selected branch is not part of this store.'], 422);
        }

        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->subDays(29)->startOfDay();
        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfDay();
        if ($startDate->diffInDays($endDate) > 366) {
            return response()->json(['success' => false, 'message' => 'Choose a date range of one year or less.'], 422);
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

        // Count scheduled shifts, not distinct employees, so attendance rates use
        // the same unit as attendance records (one employee per scheduled day).
        $scheduledCount = ShiftSchedule::query()
            ->whereBetween('schedule_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereHas('employee', function ($q) use ($storeId, $branchId) {
                $q->where('store_id', $storeId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

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
        $halfDayCount = (int) ($attendanceStats['half_day'] ?? 0);
        $holidayCount = (int) ($attendanceStats['holiday'] ?? 0);
        $eligibleScheduledCount = max(0, $scheduledCount - $leaveCount - $holidayCount);

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

        $performanceEndDate = min($endDate->toDateString(), now()->toDateString());
        $employeePerformance = collect();
        if ($startDate->toDateString() <= $performanceEndDate) {
            $employeePerformance = DB::table('shift_schedules as ss')
                ->join('employees as e', 'e.id', '=', 'ss.employee_id')
                ->leftJoin('attendances as a', function ($join) {
                    $join->on('a.schedule_id', '=', 'ss.id')->whereNull('a.deleted_at');
                })
                ->where('e.store_id', $storeId)
                ->when($branchId, fn ($query) => $query->where('e.branch_id', $branchId))
                ->whereBetween('ss.schedule_date', [$startDate->toDateString(), $performanceEndDate])
                ->whereIn('ss.status', ['scheduled', 'completed'])
                ->whereNull('ss.deleted_at')
                ->selectRaw('e.id as employee_id, e.fname, e.lname, e.department, COUNT(ss.id) as scheduled_shifts')
                ->selectRaw("SUM(CASE WHEN a.status IN ('present', 'late', 'half_day') THEN 1 ELSE 0 END) as attended_shifts")
                ->selectRaw("SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_shifts")
                ->selectRaw("SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_shifts")
                ->selectRaw("SUM(CASE WHEN a.status = 'absent' OR a.id IS NULL THEN 1 ELSE 0 END) as missed_or_unrecorded_shifts")
                ->selectRaw("SUM(CASE WHEN a.status = 'on_leave' OR a.status = 'holiday' THEN 1 ELSE 0 END) as excused_shifts")
                ->selectRaw('COALESCE(SUM(a.late_minutes), 0) as late_minutes')
                ->selectRaw('COALESCE(SUM(a.total_worked_minutes), 0) as worked_minutes')
                ->groupBy('e.id', 'e.fname', 'e.lname', 'e.department')
                ->get()
                ->map(function ($row) {
                    $eligible = max(0, (int) $row->scheduled_shifts - (int) $row->excused_shifts);
                    $attended = (int) $row->attended_shifts;
                    $present = (int) $row->present_shifts;
                    $late = (int) $row->late_shifts;
                    $reliability = $eligible > 0 ? round(($attended / $eligible) * 100, 1) : null;
                    $punctuality = ($present + $late) > 0 ? round(($present / ($present + $late)) * 100, 1) : null;

                    return [
                        'employee_id' => (int) $row->employee_id,
                        'name' => trim(($row->fname ?? '') . ' ' . ($row->lname ?? '')),
                        'department' => $row->department ?: 'Unassigned',
                        'scheduled_shifts' => (int) $row->scheduled_shifts,
                        'attended_shifts' => $attended,
                        'reliability_rate' => $reliability,
                        'punctuality_rate' => $punctuality,
                        'late_shifts' => $late,
                        'missed_or_unrecorded_shifts' => (int) $row->missed_or_unrecorded_shifts,
                        'late_minutes' => (int) $row->late_minutes,
                        'worked_hours' => round(((int) $row->worked_minutes) / 60, 1),
                    ];
                })
                ->sortBy(fn ($row) => [$row['reliability_rate'] ?? 101, -$row['late_shifts'], $row['name']])
                ->take(10)
                ->values();
        }

        $attendanceRate = $eligibleScheduledCount > 0
            ? round((($presentCount + $lateCount + $halfDayCount) / $eligibleScheduledCount) * 100, 2)
            : 0;

        $lateRate = $eligibleScheduledCount > 0
            ? round(($lateCount / $eligibleScheduledCount) * 100, 2)
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
                    'half_day' => $halfDayCount,
                    'holiday' => $holidayCount,
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
                    'employee_performance' => $employeePerformance,
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
                ->distinct('employee_id')
                ->count('employee_id'),
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
        $storeId = (int) $user->store_id;
        $timezone = config('app.timezone', 'Asia/Manila');
        $today = Carbon::now($timezone);
        $startDate = $today->copy()->startOfWeek();
        $endDate = $today->copy()->endOfWeek();

        // Keep the date column index usable. Attendance dates are stored as dates,
        // while the dashboard week is calculated in the application's timezone.
        $attendance = Attendance::whereHas('employee', fn($q) => $q->where('store_id', $storeId))
            ->select(['attendance_date', 'status'])
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy(fn ($record) => Carbon::parse($record->attendance_date)->toDateString());

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
                    if ($record->status === 'present') {
                        $dayData['present']++;
                    } elseif ($record->status === 'late') {
                        $dayData['late']++;
                    } elseif ($record->status === 'absent') {
                        $dayData['absent']++;
                    } elseif ($record->status === 'on_leave') {
                        $dayData['leave']++;
                    } elseif ($record->status === 'half_day') {
                        // A completed half-day still counts as attended.
                        $dayData['present']++;
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
