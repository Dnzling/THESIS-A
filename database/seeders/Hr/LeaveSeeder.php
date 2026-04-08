<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\Leave;
use App\Models\Hr\Employee;
use App\Models\Core\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        // Get 3 specific employees (Ahbram Carra - Store Admin, Adrian Lacea - Store Manager, Cash Gshock - Cashier)
        $employees = Employee::whereIn('user_id', [2, 3, 6])->get();
        
        // Get HR Manager and Store Admin for approval
        $hrManager = User::find(4); // Edwin Vasquez
        $storeAdmin = User::find(2); // Ahbram Carra
        
        $leaves = [
            // ============ EMPLOYEE 1: Ahbram Carra (Store Admin) - user_id 2 ============
            [
                'employee_id' => $employees->where('user_id', 2)->first()->id ?? 1,
                'leave_type' => 'vacation',
                'start_date' => '2026-01-05',
                'end_date' => '2026-01-09',
                'total_days' => 5,
                'reason' => 'Family vacation to Baguio',
                'attachment_path' => '/uploads/leaves/vacation_ahbram_jan2026.pdf',
                'status' => 'approved',
                'approved_by' => $hrManager->id,
                'rejected_reason' => null,
                'approved_at' => Carbon::parse('2025-12-20 10:30:00'),
                'created_at' => Carbon::parse('2025-12-15 14:25:00'),
                'updated_at' => Carbon::parse('2025-12-20 10:30:00'),
                'deleted_at' => null,
            ],
            [
                'employee_id' => $employees->where('user_id', 2)->first()->id ?? 1,
                'leave_type' => 'personal',
                'start_date' => '2026-01-19',
                'end_date' => '2026-01-20',
                'total_days' => 2,
                'reason' => 'Personal errands and medical checkup',
                'attachment_path' => null,
                'status' => 'pending',
                'approved_by' => null,
                'rejected_reason' => null,
                'approved_at' => null,
                'created_at' => Carbon::parse('2026-01-10 09:15:00'),
                'updated_at' => Carbon::parse('2026-01-10 09:15:00'),
                'deleted_at' => null,
            ],
            
            // ============ EMPLOYEE 2: Adrian Lacea (Store Manager) - user_id 3 ============
            [
                'employee_id' => $employees->where('user_id', 3)->first()->id ?? 2,
                'leave_type' => 'sick',
                'start_date' => '2026-01-12',
                'end_date' => '2026-01-14',
                'total_days' => 3,
                'reason' => 'Influenza and severe colds',
                'attachment_path' => '/uploads/leaves/medical_certificate_adrian_jan2026.pdf',
                'status' => 'approved',
                'approved_by' => $hrManager->id,
                'rejected_reason' => null,
                'approved_at' => Carbon::parse('2026-01-11 15:45:00'),
                'created_at' => Carbon::parse('2026-01-11 08:30:00'),
                'updated_at' => Carbon::parse('2026-01-11 15:45:00'),
                'deleted_at' => null,
            ],
            [
                'employee_id' => $employees->where('user_id', 3)->first()->id ?? 2,
                'leave_type' => 'bereavement',
                'start_date' => '2026-01-26',
                'end_date' => '2026-01-28',
                'total_days' => 3,
                'reason' => 'Death of grandmother',
                'attachment_path' => '/uploads/leaves/death_certificate_adrian_jan2026.pdf',
                'status' => 'approved',
                'approved_by' => $hrManager->id,
                'rejected_reason' => null,
                'approved_at' => Carbon::parse('2026-01-25 11:20:00'),
                'created_at' => Carbon::parse('2026-01-25 09:10:00'),
                'updated_at' => Carbon::parse('2026-01-25 11:20:00'),
                'deleted_at' => null,
            ],
            
            // ============ EMPLOYEE 3: Cash Gshock (Cashier) - user_id 6 ============
            [
                'employee_id' => $employees->where('user_id', 6)->first()->id ?? 3,
                'leave_type' => 'sick',
                'start_date' => '2026-01-07',
                'end_date' => '2026-01-08',
                'total_days' => 2,
                'reason' => 'Food poisoning',
                'attachment_path' => '/uploads/leaves/medical_certificate_cash_jan2026.pdf',
                'status' => 'approved',
                'approved_by' => $storeAdmin->id,
                'rejected_reason' => null,
                'approved_at' => Carbon::parse('2026-01-07 09:30:00'),
                'created_at' => Carbon::parse('2026-01-07 07:15:00'),
                'updated_at' => Carbon::parse('2026-01-07 09:30:00'),
                'deleted_at' => null,
            ],
            [
                'employee_id' => $employees->where('user_id', 6)->first()->id ?? 3,
                'leave_type' => 'personal',
                'start_date' => '2026-01-21',
                'end_date' => '2026-01-21',
                'total_days' => 1,
                'reason' => 'Dental appointment',
                'attachment_path' => '/uploads/leaves/dental_appointment_cash.pdf',
                'status' => 'approved',
                'approved_by' => $storeAdmin->id,
                'rejected_reason' => null,
                'approved_at' => Carbon::parse('2026-01-20 14:50:00'),
                'created_at' => Carbon::parse('2026-01-19 16:20:00'),
                'updated_at' => Carbon::parse('2026-01-20 14:50:00'),
                'deleted_at' => null,
            ],
            [
                'employee_id' => $employees->where('user_id', 6)->first()->id ?? 3,
                'leave_type' => 'vacation',
                'start_date' => '2026-01-29',
                'end_date' => '2026-01-30',
                'total_days' => 2,
                'reason' => 'Extended weekend trip to Tagaytay',
                'attachment_path' => null,
                'status' => 'pending',
                'approved_by' => null,
                'rejected_reason' => null,
                'approved_at' => null,
                'created_at' => Carbon::parse('2026-01-15 11:05:00'),
                'updated_at' => Carbon::parse('2026-01-15 11:05:00'),
                'deleted_at' => null,
            ],
        ];

        // Insert all leaves
        foreach ($leaves as $leave) {
            Leave::create($leave);
        }
    }
}