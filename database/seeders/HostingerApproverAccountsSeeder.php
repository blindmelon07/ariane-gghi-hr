<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class HostingerApproverAccountsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'hr_admin',       'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'approver',       'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'security_guard', 'guard_name' => 'web']);

        // HR Admin — Step 1 approver
        $hr = User::updateOrCreate(
            ['employee_code' => 'HR001'],
            [
                'name'      => 'HR Admin',
                'email'     => null,
                'password'  => 'hr@ariane2024',
                'role'      => 'hr_admin',
                'is_active' => true,
            ]
        );
        $hr->syncRoles('hr_admin');
        $this->command->info("HR Admin: {$hr->name} [{$hr->employee_code}]");

        // Medical Director — Step 2 approver
        $medDir = User::updateOrCreate(
            ['employee_code' => 'MEDDIR001'],
            [
                'name'      => 'Mario Euric Alerta',
                'email'     => null,
                'password'  => 'meddir@ariane2024',
                'role'      => 'approver',
                'is_active' => true,
            ]
        );
        $medDir->syncRoles('approver');
        $this->command->info("Medical Director: {$medDir->name} [{$medDir->employee_code}]");

        // CEO — Step 3 approver
        $ceo = User::updateOrCreate(
            ['employee_code' => 'CEO001'],
            [
                'name'      => 'Egene P. Sumawang',
                'email'     => null,
                'password'  => 'ceo@ariane2024',
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );
        $ceo->syncRoles('super_admin');
        $this->command->info("CEO: {$ceo->name} [{$ceo->employee_code}]");

        // Security Guard
        $guard = User::updateOrCreate(
            ['employee_code' => 'GUARD001'],
            [
                'name'      => 'Security Guard',
                'email'     => null,
                'password'  => 'guard@ariane2024',
                'role'      => 'security_guard',
                'is_active' => true,
            ]
        );
        $guard->syncRoles('security_guard');
        $this->command->info("Security Guard: {$guard->name} [{$guard->employee_code}]");
    }
}
