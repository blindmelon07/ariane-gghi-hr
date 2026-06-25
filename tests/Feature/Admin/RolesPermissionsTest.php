<?php

use App\Livewire\Admin\RolesPermissions;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function superAdmin(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'super_admin']);
    $user->syncRoles('super_admin');
    return $user;
}

function testRole(?string $name = null): Role
{
    static $n = 0;
    $n++;
    return Role::firstOrCreate(['name' => $name ?? "role_{$n}", 'guard_name' => 'web']);
}

function testPerm(?string $name = null): Permission
{
    static $n = 0;
    $n++;
    return Permission::firstOrCreate(['name' => $name ?? "perm_{$n}", 'guard_name' => 'web']);
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('allows super_admin to access roles-permissions page', function () {
        $this->actingAs(superAdmin())
            ->get('/admin/roles-permissions')
            ->assertOk();
    });

    it('blocks hr_admin from roles-permissions page', function () {
        Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'hr_admin']);
        $user->syncRoles('hr_admin');
        $this->actingAs($user)->get('/admin/roles-permissions')->assertForbidden();
    });
});

// ── Role selection ─────────────────────────────────────────────────────────────

describe('role selection', function () {
    it('loads the role permissions as string ids on selectRole', function () {
        $role = testRole();
        $perm = testPerm();
        $role->givePermissionTo($perm);

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('selectRole', $role->id)
            ->assertSet('selectedRoleId', $role->id)
            ->assertSet('rolePermissions', [(string) $perm->id]);
    });

    it('clears rolePermissions when selecting a role with no permissions', function () {
        $role = testRole();

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('selectRole', $role->id)
            ->assertSet('rolePermissions', []);
    });
});

// ── Save permissions ──────────────────────────────────────────────────────────

describe('save permissions', function () {
    it('syncs permissions to the role (string ids)', function () {
        $role = testRole();
        $p1   = testPerm();
        $p2   = testPerm();

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('selectRole', $role->id)
            ->set('rolePermissions', [(string) $p1->id, (string) $p2->id])
            ->call('saveRolePermissions');

        $role->refresh();
        expect($role->permissions->pluck('id')->sort()->values()->toArray())
            ->toBe(collect([$p1->id, $p2->id])->sort()->values()->toArray());
    });

    it('revokes all permissions when saved with empty array', function () {
        $role = testRole();
        $perm = testPerm();
        $role->givePermissionTo($perm);

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('selectRole', $role->id)
            ->set('rolePermissions', [])
            ->call('saveRolePermissions');

        expect($role->fresh()->permissions)->toBeEmpty();
    });
});

// ── Create role ───────────────────────────────────────────────────────────────

describe('create role', function () {
    it('creates a new role', function () {
        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->set('newRoleName', 'test_new_role')
            ->call('createRole');

        expect(Role::where('name', 'test_new_role')->exists())->toBeTrue();
    });

    it('requires role name of at least 2 characters', function () {
        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->set('newRoleName', 'x')
            ->call('createRole')
            ->assertHasErrors('newRoleName');
    });

    it('rejects duplicate role name', function () {
        testRole('duplicate_role');

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->set('newRoleName', 'duplicate_role')
            ->call('createRole')
            ->assertHasErrors('newRoleName');
    });
});

// ── Create permission ─────────────────────────────────────────────────────────

describe('create permission', function () {
    it('creates a new permission', function () {
        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->set('newPermissionName', 'manage testing')
            ->call('createPermission');

        expect(Permission::where('name', 'manage testing')->exists())->toBeTrue();
    });

    it('rejects duplicate permission name', function () {
        testPerm('view logs');

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->set('newPermissionName', 'view logs')
            ->call('createPermission')
            ->assertHasErrors('newPermissionName');
    });
});

// ── Delete role ───────────────────────────────────────────────────────────────

describe('delete role', function () {
    it('deletes a role from the database', function () {
        $role = testRole('deletable_role');

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('confirmDelete', $role->id)
            ->call('deleteRole');

        expect(Role::where('name', 'deletable_role')->exists())->toBeFalse();
    });

    it('clears selectedRoleId when the selected role is deleted', function () {
        $role = testRole('to_be_deleted');

        Livewire::actingAs(superAdmin())
            ->test(RolesPermissions::class)
            ->call('selectRole', $role->id)
            ->call('confirmDelete', $role->id)
            ->call('deleteRole')
            ->assertSet('selectedRoleId', null)
            ->assertSet('rolePermissions', []);
    });
});
