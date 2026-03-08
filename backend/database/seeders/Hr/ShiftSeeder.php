<?php
// database/seeders/Hr/ShiftSeeder.php

namespace Database\Seeders\Hr;

use Illuminate\Database\Seeder;
use App\Models\Hr\Shift;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Morning Shift',
                'code' => 'MORNING',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'total_hours' => 8.00,
                'color' => '#3b82f6',
                'is_active' => true,
                'description' => 'Regular morning shift',
            ],
            [
                'name' => 'Night Shift',
                'code' => 'NIGHT',
                'start_time' => '20:00:00',
                'end_time' => '05:00:00',
                'break_start' => '01:00:00',
                'break_end' => '02:00:00',
                'total_hours' => 8.00,
                'color' => '#1e40af',
                'is_active' => true,
                'description' => 'Night shift',
            ],
            [
                'name' => 'Flexi Shift',
                'code' => 'FLEXI',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_start' => '13:00:00',
                'break_end' => '14:00:00',
                'total_hours' => 8.00,
                'color' => '#10b981',
                'is_active' => true,
                'description' => 'Flexible timing shift',
            ],
            [
                'name' => 'Part Time',
                'code' => 'PART',
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'total_hours' => 4.00,
                'color' => '#f59e0b',
                'is_active' => true,
                'description' => 'Part time shift',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}