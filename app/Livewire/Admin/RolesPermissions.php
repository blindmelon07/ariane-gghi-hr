<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissions extends Component
{
    public ?int $selectedRoleId = null;
    public string $newRoleName = '';
    public string $newPermissionName = '';
    public array $rolePermissions = [];

    public bool $showNewRoleModal = false;
    public bool $showNewPermissionModal = false;
    public bool $confirmDeleteRole = false;
    public ?int $deleteRoleId = null;

    public function selectRole(int $id): void
    {
        $this->selectedRoleId = $id;
        $role = Role::with('permissions')->find($id);
        $this->rolePermissions = $role
            ? $role->permissions->pluck('id')->map(fn ($v) => (string) $v)->toArray()
            : [];
    }

    public function saveRolePermissions(): void
    {
        $role = Role::findOrFail($this->selectedRoleId);
        $role->syncPermissions(array_map('intval', $this->rolePermissions));
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        session()->flash('success', "Permissions for \"{$role->name}\" saved.");
    }

    public function createRole(): void
    {
        $this->validate(['newRoleName' => 'required|string|min:2|max:50|unique:roles,name']);
        Role::create(['name' => trim($this->newRoleName)]);
        $this->reset('newRoleName', 'showNewRoleModal');
        session()->flash('success', 'Role created.');
    }

    public function createPermission(): void
    {
        $this->validate(['newPermissionName' => 'required|string|min:2|max:100|unique:permissions,name']);
        Permission::create(['name' => trim($this->newPermissionName)]);
        $this->reset('newPermissionName', 'showNewPermissionModal');
        session()->flash('success', 'Permission created.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteRoleId = $id;
        $this->confirmDeleteRole = true;
    }

    public function deleteRole(): void
    {
        $role = Role::find($this->deleteRoleId);
        if ($role) {
            if ($this->selectedRoleId === $role->id) {
                $this->selectedRoleId = null;
                $this->rolePermissions = [];
            }
            $role->delete();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            session()->flash('success', "Role \"{$role->name}\" deleted.");
        }
        $this->reset('confirmDeleteRole', 'deleteRoleId');
    }

    public function render()
    {
        return view('livewire.admin.roles-permissions', [
            'roles'       => Role::withCount(['permissions', 'users'])->get(),
            'permissions' => Permission::orderBy('name')->get(),
            'selectedRole' => $this->selectedRoleId ? Role::find($this->selectedRoleId) : null,
        ]);
    }
}
