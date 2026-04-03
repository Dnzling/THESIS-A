<?php

namespace Database\Seeders\Hr;

use App\Models\Hr\PayPeriod;
use App\Models\Core\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayPeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Get user IDs for created_by field
        $superAdmin = 1; // Denz Declarado
        $hrManager = 4; // Edwin Vasquez
        $accountant = 5; // MC Mendoza

        $payPeriods = [
            // January 2026 - Draft
            [
                'name' => 'Pay Period 1 - January 2026 (1st Half)',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-15',
                'cutoff_date' => '2026-01-20',
                'status' => 'draft',
                'created_by' => $hrManager,
                'notes' => 'First half of January 2026 payroll period',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - January 2026 (2nd Half)',
                'start_date' => '2026-01-16',
                'end_date' => '2026-01-31',
                'cutoff_date' => '2026-02-05',
                'status' => 'draft',
                'created_by' => $hrManager,
                'notes' => 'Second half of January 2026 payroll period',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            
            // February 2026 - Processing
            [
                'name' => 'Pay Period 1 - February 2026 (1st Half)',
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-15',
                'cutoff_date' => '2026-02-20',
                'status' => 'processing',
                'created_by' => $hrManager,
                'notes' => 'First half of February 2026 - Currently being processed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - February 2026 (2nd Half)',
                'start_date' => '2026-02-16',
                'end_date' => '2026-02-28',
                'cutoff_date' => '2026-03-05',
                'status' => 'draft',
                'created_by' => $hrManager,
                'notes' => 'Second half of February 2026',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            
            // March 2026 - Locked
            [
                'name' => 'Pay Period 1 - March 2026 (1st Half)',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-15',
                'cutoff_date' => '2026-03-20',
                'status' => 'locked',
                'created_by' => $accountant,
                'notes' => 'First half of March 2026 - Locked after approval',
                'created_at' => Carbon::now()->subDays(15),
                'updated_at' => Carbon::now()->subDays(5),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - March 2026 (2nd Half)',
                'start_date' => '2026-03-16',
                'end_date' => '2026-03-31',
                'cutoff_date' => '2026-04-05',
                'status' => 'locked',
                'created_by' => $accountant,
                'notes' => 'Second half of March 2026 - Locked after approval',
                'created_at' => Carbon::now()->subDays(20),
                'updated_at' => Carbon::now()->subDays(10),
                'deleted_at' => null,
            ],
            
            // April 2026 - Completed
            [
                'name' => 'Pay Period 1 - April 2026 (1st Half)',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-15',
                'cutoff_date' => '2026-04-20',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'First half of April 2026 - Successfully processed and distributed',
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now()->subDays(30),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - April 2026 (2nd Half)',
                'start_date' => '2026-04-16',
                'end_date' => '2026-04-30',
                'cutoff_date' => '2026-05-05',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'Second half of April 2026 - Successfully processed and distributed',
                'created_at' => Carbon::now()->subDays(50),
                'updated_at' => Carbon::now()->subDays(35),
                'deleted_at' => null,
            ],
            
            // May 2026 - Completed
            [
                'name' => 'Pay Period 1 - May 2026 (1st Half)',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-15',
                'cutoff_date' => '2026-05-20',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'First half of May 2026',
                'created_at' => Carbon::now()->subDays(75),
                'updated_at' => Carbon::now()->subDays(60),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - May 2026 (2nd Half)',
                'start_date' => '2026-05-16',
                'end_date' => '2026-05-31',
                'cutoff_date' => '2026-06-05',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Second half of May 2026',
                'created_at' => Carbon::now()->subDays(80),
                'updated_at' => Carbon::now()->subDays(65),
                'deleted_at' => null,
            ],
            
            // June 2026 - Completed
            [
                'name' => 'Pay Period 1 - June 2026 (1st Half)',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-15',
                'cutoff_date' => '2026-06-20',
                'status' => 'completed',
                'created_by' => $superAdmin,
                'notes' => 'First half of June 2026',
                'created_at' => Carbon::now()->subDays(105),
                'updated_at' => Carbon::now()->subDays(90),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - June 2026 (2nd Half)',
                'start_date' => '2026-06-16',
                'end_date' => '2026-06-30',
                'cutoff_date' => '2026-07-05',
                'status' => 'completed',
                'created_by' => $superAdmin,
                'notes' => 'Second half of June 2026',
                'created_at' => Carbon::now()->subDays(110),
                'updated_at' => Carbon::now()->subDays(95),
                'deleted_at' => null,
            ],
            
            // July 2026 - Completed
            [
                'name' => 'Pay Period 1 - July 2026 (1st Half)',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-15',
                'cutoff_date' => '2026-07-20',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'First half of July 2026',
                'created_at' => Carbon::now()->subDays(135),
                'updated_at' => Carbon::now()->subDays(120),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - July 2026 (2nd Half)',
                'start_date' => '2026-07-16',
                'end_date' => '2026-07-31',
                'cutoff_date' => '2026-08-05',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Second half of July 2026',
                'created_at' => Carbon::now()->subDays(140),
                'updated_at' => Carbon::now()->subDays(125),
                'deleted_at' => null,
            ],
            
            // August 2026 - Completed
            [
                'name' => 'Pay Period 1 - August 2026 (1st Half)',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-15',
                'cutoff_date' => '2026-08-20',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'First half of August 2026',
                'created_at' => Carbon::now()->subDays(165),
                'updated_at' => Carbon::now()->subDays(150),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - August 2026 (2nd Half)',
                'start_date' => '2026-08-16',
                'end_date' => '2026-08-31',
                'cutoff_date' => '2026-09-05',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'Second half of August 2026',
                'created_at' => Carbon::now()->subDays(170),
                'updated_at' => Carbon::now()->subDays(155),
                'deleted_at' => null,
            ],
            
            // September 2026 - Completed
            [
                'name' => 'Pay Period 1 - September 2026 (1st Half)',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-15',
                'cutoff_date' => '2026-09-20',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'First half of September 2026',
                'created_at' => Carbon::now()->subDays(195),
                'updated_at' => Carbon::now()->subDays(180),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - September 2026 (2nd Half)',
                'start_date' => '2026-09-16',
                'end_date' => '2026-09-30',
                'cutoff_date' => '2026-10-05',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Second half of September 2026',
                'created_at' => Carbon::now()->subDays(200),
                'updated_at' => Carbon::now()->subDays(185),
                'deleted_at' => null,
            ],
            
            // October 2026 - Completed
            [
                'name' => 'Pay Period 1 - October 2026 (1st Half)',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-15',
                'cutoff_date' => '2026-10-20',
                'status' => 'completed',
                'created_by' => $superAdmin,
                'notes' => 'First half of October 2026',
                'created_at' => Carbon::now()->subDays(225),
                'updated_at' => Carbon::now()->subDays(210),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - October 2026 (2nd Half)',
                'start_date' => '2026-10-16',
                'end_date' => '2026-10-31',
                'cutoff_date' => '2026-11-05',
                'status' => 'completed',
                'created_by' => $superAdmin,
                'notes' => 'Second half of October 2026',
                'created_at' => Carbon::now()->subDays(230),
                'updated_at' => Carbon::now()->subDays(215),
                'deleted_at' => null,
            ],
            
            // November 2026 - Completed
            [
                'name' => 'Pay Period 1 - November 2026 (1st Half)',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-15',
                'cutoff_date' => '2026-11-20',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'First half of November 2026',
                'created_at' => Carbon::now()->subDays(255),
                'updated_at' => Carbon::now()->subDays(240),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - November 2026 (2nd Half)',
                'start_date' => '2026-11-16',
                'end_date' => '2026-11-30',
                'cutoff_date' => '2026-12-05',
                'status' => 'completed',
                'created_by' => $accountant,
                'notes' => 'Second half of November 2026',
                'created_at' => Carbon::now()->subDays(260),
                'updated_at' => Carbon::now()->subDays(245),
                'deleted_at' => null,
            ],
            
            // December 2026 - Completed
            [
                'name' => 'Pay Period 1 - December 2026 (1st Half)',
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-15',
                'cutoff_date' => '2026-12-20',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'First half of December 2026',
                'created_at' => Carbon::now()->subDays(285),
                'updated_at' => Carbon::now()->subDays(270),
                'deleted_at' => null,
            ],
            [
                'name' => 'Pay Period 2 - December 2026 (2nd Half)',
                'start_date' => '2026-12-16',
                'end_date' => '2026-12-31',
                'cutoff_date' => '2027-01-05',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Second half of December 2026 - Year-end payroll',
                'created_at' => Carbon::now()->subDays(290),
                'updated_at' => Carbon::now()->subDays(275),
                'deleted_at' => null,
            ],
        ];

        // Insert all pay periods
        foreach ($payPeriods as $payPeriod) {
            PayPeriod::create($payPeriod);
        }

        // Add some deleted/archived pay periods (soft deleted)
        $deletedPayPeriods = [
            [
                'name' => 'Pay Period 1 - November 2025 (1st Half)',
                'start_date' => '2025-11-01',
                'end_date' => '2025-11-15',
                'cutoff_date' => '2025-11-20',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Archived - November 2025 first half',
                'created_at' => Carbon::now()->subDays(365),
                'updated_at' => Carbon::now()->subDays(350),
                'deleted_at' => Carbon::now()->subDays(30),
                'deleted_by' => $superAdmin,
            ],
            [
                'name' => 'Pay Period 2 - November 2025 (2nd Half)',
                'start_date' => '2025-11-16',
                'end_date' => '2025-11-30',
                'cutoff_date' => '2025-12-05',
                'status' => 'completed',
                'created_by' => $hrManager,
                'notes' => 'Archived - November 2025 second half',
                'created_at' => Carbon::now()->subDays(370),
                'updated_at' => Carbon::now()->subDays(355),
                'deleted_at' => Carbon::now()->subDays(30),
                'deleted_by' => $superAdmin,
            ],
        ];

        foreach ($deletedPayPeriods as $payPeriod) {
            PayPeriod::create($payPeriod);
        }
    }
}