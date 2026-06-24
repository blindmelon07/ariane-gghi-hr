<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the super_admin role exists with all permissions
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $role->syncPermissions(\Spatie\Permission\Models\Permission::all());

        $user = User::firstOrCreate(
            ['email' => 'superadmin@hrportal.local'],
            [
                'name'          => 'Super Administrator',
                'employee_code' => 'SUPERADMIN',
                'password'      => Hash::make('superadmin1234'),
                'role'          => 'super_admin',
                'is_active'     => true,
            ]
        );

        $user->syncRoles('super_admin');
    }
}
