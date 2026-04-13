<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveTestEmployeesSeeder extends Seeder
{
    /**
     * Remove test employees previously inserted by AddTestEmployeesSeeder
     */
    public function run()
    {
        $firstNames = ['Liam','Olivia','Noah','Emma','Oliver','Ava','Elijah','Sophia','William','Isabella','James','Mia','Benjamin','Charlotte','Lucas','Amelia','Henry','Harper','Alexander','Evelyn'];
        $lastNames = ['Garcia','Smith','Johnson','Brown','Taylor','Anderson','Thomas','Jackson','White','Harris','Martin','Thompson','Moore','Clark','Walker','Rodriguez','Lee','Hall','Allen','Young'];

        $deleted = 0;
        foreach ($firstNames as $fn) {
            foreach ($lastNames as $ln) {
                $deleted += DB::table('employees')->where('fname', $fn)->where('lname', $ln)->delete();
            }
        }

        $this->command->info('Removed ' . $deleted . ' test employees (matching seeded name lists).');
    }
}
