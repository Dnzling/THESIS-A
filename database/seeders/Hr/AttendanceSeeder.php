<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Get the 3 specific employees
        $employee1 = Employee::where('user_id', 2)->first(); // Ahbram Carra - Store Admin
        $employee2 = Employee::where('user_id', 3)->first(); // Adrian Lacea - Store Manager
        $employee3 = Employee::where('user_id', 6)->first(); // Cash Gshock - Cashier
        
        // Get shifts (assuming morning shift is id 1)
        $morningShift = Shift::find(1);
        
        // Get approvers
        $hrManager = User::find(4); // Edwin Vasquez
        $storeAdmin = User::find(2); // Ahbram Carra
        
        $attendances = [];
        
        // Generate attendance for January 2026
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-01-31');
        
        foreach ([$employee1, $employee2, $employee3] as $employee) {
            $currentDate = clone $startDate;
            
            while ($currentDate <= $endDate) {
                // Check if employee has leave on this date
                $leaveStatus = $this->checkIfOnLeave($employee, $currentDate);
                
                if ($leaveStatus) {
                    // On leave - create on_leave record
                    $attendances[] = $this->createLeaveAttendanceRecord(
                        $employee,
                        clone $currentDate,
                        $morningShift->id ?? 1,
                        $leaveStatus
                    );
                } else {
                    // Regular working day (skip weekends)
                    if (!$currentDate->isWeekend()) {
                        $attendances[] = $this->createAttendanceRecord(
                            $employee,
                            clone $currentDate,
                            $morningShift->id ?? 1
                        );
                    }
                }
                
                $currentDate->addDay();
            }
        }
        
        // Insert all attendance records
        foreach ($attendances as $attendance) {
            Attendance::create($attendance);
        }
    }
    
    private function createAttendanceRecord($employee, $date, $shiftId): array
    {
        // Define shift times (8:00 AM - 5:00 PM)
        $shiftStart = Carbon::parse($date->format('Y-m-d') . ' 08:00:00');
        $shiftEnd = Carbon::parse($date->format('Y-m-d') . ' 17:00:00');
        
        // Get actual clock times based on employee patterns
        $clockIn = $this->getClockIn($employee, $date, $shiftStart);
        $clockOut = $this->getClockOut($employee, $date, $shiftEnd);
        $breakStart = $this->getBreakStart($employee, $date);
        $breakEnd = $this->getBreakEnd($employee, $date);
        
        // Calculate minutes
        $lateMinutes = $clockIn > $shiftStart ? $clockIn->diffInMinutes($shiftStart) : 0;
        $earlyDepartureMinutes = $clockOut < $shiftEnd ? $shiftEnd->diffInMinutes($clockOut) : 0;
        
        // Calculate break minutes
        $breakMinutes = 0;
        if ($breakStart && $breakEnd) {
            $breakMinutes = $breakStart->diffInMinutes($breakEnd);
        }
        
        // Calculate total worked minutes
        $totalWorkedMinutes = $clockIn->diffInMinutes($clockOut) - $breakMinutes;
        
        // Calculate overtime (minutes worked beyond shift end)
        $overtimeMinutes = $clockOut > $shiftEnd ? $shiftEnd->diffInMinutes($clockOut) : 0;
        
        // Determine status
        $status = $this->determineStatus($lateMinutes, $earlyDepartureMinutes, $totalWorkedMinutes);
        
        // Get approver based on employee
        $approvedBy = $this->getApprover($employee);
        $approvedAt = $status != 'absent' ? Carbon::now()->subDays(rand(1, 5)) : null;
        
        // Get IP addresses and locations
        $ipAddresses = $this->getIpAddresses();
        $locations = $this->getLocations();
        
        return [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'attendance_date' => $date->format('Y-m-d'),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'break_minutes' => $breakMinutes,
            'total_worked_minutes' => $totalWorkedMinutes,
            'status' => $status,
            'notes' => $this->generateNotes($status, $lateMinutes, $overtimeMinutes),
            'clock_in_ip' => $ipAddresses['in'],
            'clock_out_ip' => $ipAddresses['out'],
            'clock_in_location' => $locations['in'],
            'clock_out_location' => $locations['out'],
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
    
    private function createLeaveAttendanceRecord($employee, $date, $shiftId, $leaveType): array
    {
        return [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'attendance_date' => $date->format('Y-m-d'),
            'clock_in' => null,
            'clock_out' => null,
            'break_start' => null,
            'break_end' => null,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'overtime_minutes' => 0,
            'break_minutes' => 0,
            'total_worked_minutes' => 0,
            'status' => 'on_leave',
            'notes' => 'On ' . $leaveType . ' leave',
            'clock_in_ip' => null,
            'clock_out_ip' => null,
            'clock_in_location' => null,
            'clock_out_location' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
    
    private function getClockIn($employee, $date, $shiftStart): Carbon
    {
        $dayOfMonth = $date->day;
        $baseTime = clone $shiftStart;
        
        switch ($employee->user_id) {
            case 2: // Ahbram Carra - Store Admin
                if ($dayOfMonth == 4) return $baseTime->addMinutes(20); // 20 mins late
                if ($dayOfMonth == 15) return $baseTime->addMinutes(45); // 45 mins late
                return $baseTime->subMinutes(5); // 5 mins early
                
            case 3: // Adrian Lacea - Store Manager
                if ($dayOfMonth == 8) return $baseTime->addMinutes(30); // 30 mins late
                if ($dayOfMonth == 22) return $baseTime->addMinutes(15); // 15 mins late
                return $baseTime->addMinutes(5); // 5 mins late
                
            case 6: // Cash Gshock - Cashier
                if ($dayOfMonth == 3) return $baseTime->addMinutes(10); // 10 mins late
                if ($dayOfMonth == 17) return $baseTime->subMinutes(10); // 10 mins early
                if ($dayOfMonth == 27) return $baseTime->addMinutes(40); // 40 mins late
                return clone $baseTime; // On time
                
            default:
                return clone $baseTime;
        }
    }
    
    private function getClockOut($employee, $date, $shiftEnd): Carbon
    {
        $dayOfMonth = $date->day;
        $dayOfWeek = $date->dayOfWeek;
        $baseTime = clone $shiftEnd;
        
        // Friday (5) is half-day for some employees
        $isFriday = $dayOfWeek == Carbon::FRIDAY;
        
        switch ($employee->user_id) {
            case 2: // Ahbram Carra - Store Admin
                if ($dayOfMonth == 10) return $baseTime->addMinutes(150); // 2.5 hrs OT
                if ($dayOfMonth == 20) return $baseTime->addMinutes(105); // 1.75 hrs OT
                if ($isFriday) return $baseTime->subMinutes(30); // Half day on Fridays
                return $baseTime->addMinutes(rand(-15, 15)); // Slight variation
                
            case 3: // Adrian Lacea - Store Manager
                if ($dayOfMonth == 5) return $baseTime->addMinutes(135); // 2.25 hrs OT
                if ($dayOfMonth == 12) return $baseTime->addMinutes(180); // 3 hrs OT
                if ($dayOfMonth == 25) return $baseTime->addMinutes(90); // 1.5 hrs OT
                return $baseTime->addMinutes(30); // Usually stays 30 mins late
                
            case 6: // Cash Gshock - Cashier
                if ($dayOfMonth == 2) return $baseTime->addMinutes(210); // 3.5 hrs OT - inventory
                if ($dayOfMonth == 16) return $baseTime->addMinutes(165); // 2.75 hrs OT - closing
                if ($dayOfMonth == 28) return $baseTime->addMinutes(75); // 1.25 hrs OT
                return $baseTime->addMinutes(rand(-10, 10)); // On time variation
                
            default:
                return clone $baseTime;
        }
    }
    
    private function getBreakStart($employee, $date): ?Carbon
    {
        $dayOfMonth = $date->day;
        $baseTime = Carbon::parse($date->format('Y-m-d') . ' 12:00:00');
        
        switch ($employee->user_id) {
            case 2: // Ahbram Carra - Store Admin
                return clone $baseTime;
                
            case 3: // Adrian Lacea - Store Manager
                if ($dayOfMonth == 5) return $baseTime->addHour(); // Late break
                if ($dayOfMonth == 12) return $baseTime->addMinutes(30);
                return clone $baseTime;
                
            case 6: // Cash Gshock - Cashier
                if ($dayOfMonth == 2) return $baseTime->subMinutes(30); // Early break
                if ($dayOfMonth == 16) return $baseTime->addMinutes(75); // Late break
                return clone $baseTime;
                
            default:
                return clone $baseTime;
        }
    }
    
    private function getBreakEnd($employee, $date): ?Carbon
    {
        $breakStart = $this->getBreakStart($employee, $date);
        if (!$breakStart) return null;
        
        $dayOfMonth = $date->day;
        
        switch ($employee->user_id) {
            case 2: // Ahbram Carra - Store Admin
                return (clone $breakStart)->addHour();
                
            case 3: // Adrian Lacea - Store Manager
                if ($dayOfMonth == 5) return (clone $breakStart)->addHour(); // 1 hr break
                if ($dayOfMonth == 12) return (clone $breakStart)->addMinutes(30); // 30 min break
                return (clone $breakStart)->addHour();
                
            case 6: // Cash Gshock - Cashier
                if ($dayOfMonth == 2) return (clone $breakStart)->addMinutes(45); // 45 min break
                if ($dayOfMonth == 16) return (clone $breakStart)->addMinutes(45);
                return (clone $breakStart)->addHour();
                
            default:
                return (clone $breakStart)->addHour();
        }
    }
    
    private function determineStatus($lateMinutes, $earlyDepartureMinutes, $totalWorkedMinutes): string
    {
        if ($totalWorkedMinutes < 240) { // Less than 4 hours
            return 'half_day';
        }
        
        if ($lateMinutes > 30) {
            return 'late';
        }
        
        if ($earlyDepartureMinutes > 30) {
            return 'late';
        }
        
        return 'present';
    }
    
    private function generateNotes($status, $lateMinutes, $overtimeMinutes): ?string
    {
        if ($status == 'half_day') {
            return 'Half-day attendance';
        }
        
        if ($status == 'late') {
            return 'Arrived ' . $lateMinutes . ' minutes late';
        }
        
        if ($overtimeMinutes > 60) {
            return 'Worked ' . round($overtimeMinutes/60, 1) . ' hours overtime';
        }
        
        return null;
    }
    
    private function checkIfOnLeave($employee, $date): ?string
    {
        $leaveDates = [
            2 => [ // Ahbram Carra
                '2026-01-05' => 'vacation', 
                '2026-01-06' => 'vacation', 
                '2026-01-07' => 'vacation', 
                '2026-01-08' => 'vacation', 
                '2026-01-09' => 'vacation',
                '2026-01-19' => 'personal', 
                '2026-01-20' => 'personal',
            ],
            3 => [ // Adrian Lacea
                '2026-01-12' => 'sick', 
                '2026-01-13' => 'sick', 
                '2026-01-14' => 'sick',
                '2026-01-26' => 'bereavement', 
                '2026-01-27' => 'bereavement', 
                '2026-01-28' => 'bereavement',
            ],
            6 => [ // Cash Gshock
                '2026-01-07' => 'sick', 
                '2026-01-08' => 'sick',
                '2026-01-21' => 'personal',
                '2026-01-29' => 'vacation', 
                '2026-01-30' => 'vacation',
            ],
        ];
        
        $dateString = $date->format('Y-m-d');
        return $leaveDates[$employee->user_id][$dateString] ?? null;
    }
    
    private function getApprover($employee): ?int
    {
        $hrManager = User::where('email', 'hr.manager@example.com')->first();
        $storeAdmin = User::where('email', 'store.admin@example.com')->first();
        
        switch ($employee->user_id) {
            case 2: // Ahbram Carra
                return $hrManager->id ?? 4;
            case 3: // Adrian Lacea
                return $hrManager->id ?? 4;
            case 6: // Cash Gshock
                return $storeAdmin->id ?? 2;
            default:
                return null;
        }
    }
    
    private function getIpAddresses(): array
    {
        $officeIps = ['192.168.1.105', '192.168.1.110', '192.168.1.115', '192.168.1.120'];
        $homeIps = ['58.69.142.35', '112.198.75.123', '124.105.45.67'];
        
        return [
            'in' => rand(0, 1) ? $officeIps[array_rand($officeIps)] : $homeIps[array_rand($homeIps)],
            'out' => rand(0, 1) ? $officeIps[array_rand($officeIps)] : $homeIps[array_rand($homeIps)],
        ];
    }
    
    private function getLocations(): array
    {
        $locations = [
            'in' => 'SM Megamall, Mandaluyong',
            'out' => 'SM Megamall, Mandaluyong',
        ];
        
        // Sometimes clock out from different location
        if (rand(0, 10) == 1) {
            $locations['out'] = 'Remote - Home Office';
        }
        
        return $locations;
    }
}