<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Yajra\Address\Seeders\AddressSeeder;

class AddressSeederProxy extends Seeder
{
    public function run(): void
    {
        $this->call(AddressSeeder::class);
    }
}
