<?php

namespace Database\Seeders;

use App\Models\Materials;
use App\Models\DailyInput;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(VendorSeeder::class);
        $this->call(MaterialSeeder::class);
        // $this->call(DailyInputSeeder::class);
    }
}
