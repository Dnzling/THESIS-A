<?php
// database/seeders/Hr/ShiftScheduleSeeder.php

namespace Database\Seeders\Hr;

use Illuminate\Database\Seeder;
use App\Models\Hr\ShiftSchedule;
use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use Carbon\Carbon;

class ShiftScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::limit(5)->get();
        $shifts = Shift::all();
        
        if ($employees->isEmpty() || $shifts->isEmpty()) {
            return;
        }

        foreach ($employees as $employee) {
            // Get a random shift
            $shift = $shifts->random();
            
            // Create a single schedule for a random date in the current month
            $scheduleDate = Carbon::now()->startOfMonth()->addDays(rand(0, Carbon::now()->daysInMonth - 1));
            
            // Skip if it's a weekend (optional)
            if (!$scheduleDate->isWeekend()) {
                ShiftSchedule::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift->id,
                    'schedule_date' => $scheduleDate->toDateString(),
                    'assigned_by' => 1, // Admin user
                    'status' => 'scheduled',
                ]);
            } else {
                // If random date falls on weekend, try a weekday instead
                $scheduleDate = Carbon::now()->startOfMonth()->nextWeekday();
                ShiftSchedule::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift->id,
                    'schedule_date' => $scheduleDate->toDateString(),
                    'assigned_by' => 1, // Admin user
                    'status' => 'scheduled',
                ]);
            }
        }
    }
}