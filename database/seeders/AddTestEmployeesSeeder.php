<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Hr\Employee;
use Illuminate\Support\Carbon;

class AddTestEmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates 10 test employees for store_id=1 using existing role_ids
     */
    public function run()
    {
        $storeId = 1;

        // collect role ids used by existing employees in store 1
        $roleIds = Employee::where('store_id', $storeId)->pluck('role_id')->unique()->filter()->values()->all();

        // pick an existing user id to associate (fallback)
        $userId = \DB::table('users')->pluck('id')->first();

        // get branch ids for the store
        $branchIds = \DB::table('branches')->where('store_id', $storeId)->pluck('id')->all();
        $branchIds = is_array($branchIds) && count($branchIds) ? $branchIds : [1];

        if (empty($roleIds)) {
            $this->command->info('No existing role_ids found for store_id=1. Seeder skipped.');
            return;
        }

        $now = Carbon::now();
        $firstNames = ['Liam','Olivia','Noah','Emma','Oliver','Ava','Elijah','Sophia','William','Isabella','James','Mia','Benjamin','Charlotte','Lucas','Amelia','Henry','Harper','Alexander','Evelyn'];
        $lastNames = ['Garcia','Smith','Johnson','Brown','Taylor','Anderson','Thomas','Jackson','White','Harris','Martin','Thompson','Moore','Clark','Walker','Rodriguez','Lee','Hall','Allen','Young'];

        $toInsert = [];
        $used = [];
        for ($i = 0; $i < 10; $i++) {
            $roleId = $roleIds[$i % count($roleIds)];
            // pick a unique realistic name
            do {
                $fname = $firstNames[array_rand($firstNames)];
                $lname = $lastNames[array_rand($lastNames)];
                $key = $fname . ' ' . $lname;
            } while (in_array($key, $used));
            $used[] = $key;

                // employees table does not have email column; omit it

            // Only include columns that exist and are mass assignable on Employee
            $toInsert[] = [
                'user_id' => $userId,
                'store_id' => $storeId,
                'branch_id' => $branchIds[$i % count($branchIds)],
                'role_id' => $roleId,
                'employee_number' => null,
                'fname' => $fname,
                'lname' => $lname,
                'phone' => null,
                'province' => null,
                'city' => null,
                'address' => null,
                'date_of_birth' => null,
                'gender' => null,
                'hire_date' => $now->toDateString(),
                'department' => null,
                'employment_type' => 'full_time',
                'salary' => null,
                'bank_account' => null,
                'tax_id' => null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
                'emergency_contact_relationship' => null,
                'id_document_path' => null,
                'contract_path' => null,
                'status' => 'active',
                'termination_date' => null,
                'termination_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert and report
        \DB::table('employees')->insert($toInsert);
        $this->command->info('Inserted ' . count($toInsert) . ' test employees for store_id=1');
    }
}
