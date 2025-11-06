<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'adminWH@gmail.com',
            'password' => '12345678',
            'role' => 'ADMIN',
        ]);
    }
}
