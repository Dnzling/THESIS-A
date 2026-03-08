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
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getTodayStats(Request $request)
    {
        $user = Auth::user();
        $storeId = $user->store_id;
        
        $today = now()->format('Y-m-d');
        
        $stats = [
            'date' => $today,
            'day_name' => now()->format('l'),
            'total_employees' => Employee::where('store_id', $storeId)->count(),
            'scheduled_today' => ShiftSchedule::where('schedule_date', $today)
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
                ->count(),
            'attended_today' => Attendance::where('attendance_date', $today)
                ->whereIn('status', ['present', 'late'])
                ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
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
        
        $attendance = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->whereHas('employee', fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('attendance_date, status, count(*) as total')
            ->groupBy('attendance_date', 'status')
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
            'total_leaves' => Leave::whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
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