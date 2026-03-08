<?php
// app/Console/Commands/GenerateFebSchedule.php

namespace App\Console\Commands;

use App\Models\Hr\Employee;
use App\Models\Hr\Shift;
use App\Models\Hr\ShiftSchedule;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateFebSchedule extends Command
{
    protected $signature = 'schedule:generate-feb';
    protected $description = 'Generate shift schedules for Feb 23-28, 2026';

    public function handle()
    {
        $this->info('Generating schedules for Feb 23-28, 2026...');

        $startDate = Carbon::parse('2026-02-23');
        $endDate = Carbon::parse('2026-02-28');
        
        // Get all active employees (adjust as needed)
        $employees = Employee::where('store_id', 1) // Your store ID
            ->whereIn('status', ['active', 'on_leave'])
            ->get();

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                
                // Determine shift based on day of week
                $shiftId = $this->getShiftForDay($employee, $dayOfWeek);
                
                if ($shiftId) {
                    // Check if schedule already exists
                    $exists = ShiftSchedule::where('employee_id', $employee->id)
                        ->where('schedule_date', $currentDate->format('Y-m-d'))
                        ->exists();

                    if (!$exists) {
                        ShiftSchedule::create([
                            'employee_id' => $employee->id,
                            'shift_id' => $shiftId,
                            'schedule_date' => $currentDate->format('Y-m-d'),
                            'generation_method' => 'manual',
                            'status' => 'scheduled',
                            'assigned_by' => 1, // Admin user ID
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $generated++;
                    } else {
                        $skipped++;
                    }
                }
                
                $currentDate->addDay();
            }
            
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine();
        $this->info("✅ Done! Generated: $generated schedules, Skipped: $skipped");
    }

    private function getShiftForDay($employee, $dayOfWeek)
    {
        // Define shift mapping based on employee role or department
        // This is where you customize based on your business rules
        
        $shifts = [
            // Weekday shifts (Mon-Fri)
            'monday' => 1,     // Morning shift (9AM-6PM)
            'tuesday' => 1,    // Morning shift
            'wednesday' => 1,   // Morning shift
            'thursday' => 2,    // Evening shift (2PM-10PM)
            'friday' => 2,      // Evening shift
            
            // Weekend shifts
            'saturday' => 3,     // Weekend shift (10AM-7PM)
            'sunday' => 3,       // Weekend shift
        ];

        // You can add logic for different employee types
        if ($employee->department === 'sales') {
            // Sales team works weekends
            return $shifts[$dayOfWeek] ?? null;
        } elseif ($employee->department === 'admin') {
            // Admin doesn't work weekends
            if (in_array($dayOfWeek, ['saturday', 'sunday'])) {
                return null;
            }
            return $shifts[$dayOfWeek] ?? null;
        }

        return $shifts[$dayOfWeek] ?? null;
    }
}