<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExecAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // HR Admin — Step 1 approver (hr_admin role)
        $hr = User::firstOrCreate(
            ['employee_code' => 'HR001'],
            [
                'name'      => 'HR Admin',
                'email'     => null,
                'password'  => Hash::make('hr@ariane2024'),
                'role'      => 'hr_admin',
                'is_active' => true,
            ]
        );
        $hr->syncRoles('hr_admin');
        $this->command->info("HR Admin: {$hr->name} [{$hr->employee_code}]");

        // CEO — Step 3 approver (super_admin role)
        $ceo = User::firstOrCreate(
            ['employee_code' => 'CEO001'],
            [
                'name'      => 'Egene P. Sumawang',
                'email'     => null,
                'password'  => Hash::make('ceo@ariane2024'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );
        $ceo->syncRoles('super_admin');

        // Medical Director — Step 2 approver (approver role)
        $medDir = User::firstOrCreate(
            ['employee_code' => 'MEDDIR001'],
            [
                'name'      => 'Mario Euric Alerta',
                'email'     => null,
                'password'  => Hash::make('meddir@ariane2024'),
                'role'      => 'approver',
                'is_active' => true,
            ]
        );
        $medDir->syncRoles('approver');

        $this->command->info("CEO: {$ceo->name} [{$ceo->employee_code}]");
        $this->command->info("Medical Director: {$medDir->name} [{$medDir->employee_code}]");
    }
}
