<?php

namespace Database\Seeders;

use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class YellowStoreOctober2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $stores = DB::table('stores')->where('name', 'Yellow Store')->get(['id', 'name']);
        if ($stores->count() !== 1) {
            throw new RuntimeException('Expected exactly one store named "Yellow Store"; found ' . $stores->count() . '.');
        }

        $storeId = (int) $stores->first()->id;
        $employees = DB::table('employees')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id']);

        if ($employees->isEmpty()) {
            throw new RuntimeException('Yellow Store has no active employees to seed attendance for.');
        }

        // Keep the sample distribution repeatable whenever this seeder is run.
        mt_srand(20261030);
        $now = now();
        $rows = [];

        foreach ($employees as $employee) {
            foreach (CarbonPeriod::create('2026-10-01', '2026-10-30') as $date) {
                $dateString = $date->toDateString();
                $clockIn = null;
                $clockOut = null;
                $scheduledClose = null;
                $workedMinutes = 0;
                $lateMinutes = 0;
                $status = 'day_off';
                $note = 'Scheduled day off';

                if (!$date->isWeekend()) {
                    $sample = mt_rand(1, 100);
                    if ($sample <= 14) {
                        $status = 'absent';
                        $note = 'Demo absence';
                    } else {
                        $clockIn = $dateString . ' 08:00:00';
                        if ($sample <= 32) {
                            $status = 'present';
                            $clockOut = $dateString . ' 11:00:00';
                            $scheduledClose = $dateString . ' 17:00:00';
                            $workedMinutes = 180;
                            $note = 'Demo short shift: 3 hours worked';
                        } else {
                            $status = 'present';
                            $clockOut = $dateString . ' 17:00:00';
                            $scheduledClose = $dateString . ' 17:00:00';
                            $workedMinutes = 480;
                            $note = 'Demo full shift: 8 hours worked';
                        }
                    }
                }

                $rows[] = [
                    'store_id' => $storeId,
                    'employee_id' => (int) $employee->id,
                    'attendance_date' => $dateString,
                    'status' => $status,
                    'note' => $note,
                    'recorded_by' => null,
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $clockOut,
                    'scheduled_close_at' => $scheduledClose,
                    'worked_minutes' => $workedMinutes,
                    'late_minutes' => $lateMinutes,
                    'overtime_minutes' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $inserted = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            $inserted += DB::table('simple_employee_attendances')->insertOrIgnore($chunk);
        }

        $this->command?->info("Seeded {$inserted} missing attendance records for {$employees->count()} Yellow Store employee(s), October 1–30, 2026. Existing records were preserved.");
    }
}
