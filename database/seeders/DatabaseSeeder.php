<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // HR Admin
        User::factory()->create([
            'name'          => 'HR Administrator',
            'employee_code' => 'ADMIN001',
            'email'         => 'admin@hrportal.local',
            'password'      => Hash::make('admin1234'),
            'role'          => 'hr_admin',
            'is_active'     => true,
        ]);

        $this->call([
            LeaveTypeSeeder::class,
            LeaveCreditsSeeder::class,
        ]);
    }
}
