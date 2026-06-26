<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HostingerApproverAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // HR Admin — Step 1 approver
        $hrRole = Role::firstOrCreate(['name' => 'hr_admin']);
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
        $this->command->info("HR Admin created: {$hr->name} [{$hr->employee_code}]");

        // Medical Director — Step 2 approver
        $approverRole = Role::firstOrCreate(['name' => 'approver']);
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
        $this->command->info("Medical Director created: {$medDir->name} [{$medDir->employee_code}]");

        // CEO — Step 3 approver
        $superRole = Role::firstOrCreate(['name' => 'super_admin']);
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
        $this->command->info("CEO created: {$ceo->name} [{$ceo->employee_code}]");
    }
}
