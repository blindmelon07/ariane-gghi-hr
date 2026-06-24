<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'approve leaves',
            'approve overtime',
            'manage payroll',
            'manage employees',
            'manage schedules',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles with their permissions
        $hr_admin = Role::firstOrCreate(['name' => 'hr_admin']);
        $hr_admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions(['approve leaves', 'approve overtime', 'view reports']);

        $approver = Role::firstOrCreate(['name' => 'approver']);
        $approver->syncPermissions(['approve leaves', 'approve overtime', 'view reports']);

        Role::firstOrCreate(['name' => 'employee']);

        // Sync existing users to their Spatie role based on the users.role column
        User::all()->each(function (User $user) {
            if ($user->role && ! $user->hasRole($user->role)) {
                $user->assignRole($user->role);
            }
        });
    }
}
