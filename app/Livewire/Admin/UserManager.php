<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool   $showModal    = false;
    public ?int   $editId       = null;
    public string $name         = '';
    public string $employeeCode = '';
    public string $role         = 'employee';
    public bool   $isActive     = true;
    public string $password     = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('employee_code', 'like', "%{$this->search}%")
            )
            ->orderBy('name')
            ->paginate(15);
    }

    public function openAdd(): void
    {
        $this->reset('editId', 'name', 'employeeCode', 'password');
        $this->role     = 'employee';
        $this->isActive = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editId       = $id;
        $this->name         = $user->name;
        $this->employeeCode = $user->employee_code;
        $this->role         = $user->role;
        $this->isActive     = $user->is_active;
        $this->password     = '';
        $this->showModal    = true;
    }

    public function save(): void
    {
        $rules = [
            'name'         => 'required|string|max:100',
            'employeeCode' => 'required|string|max:20|unique:users,employee_code' . ($this->editId ? ",{$this->editId}" : ''),
            'role'         => 'required|in:employee,hr_admin,manager,department_head,approver,super_admin,security_guard,head_nurse',
            'isActive'     => 'boolean',
        ];

        if (! $this->editId) {
            $rules['password'] = 'required|string|min:6';
        } elseif ($this->password !== '') {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules);

        if ($this->editId) {
            $user = User::findOrFail($this->editId);
            $data = [
                'name'          => $this->name,
                'employee_code' => $this->employeeCode,
                'role'          => $this->role,
                'is_active'     => $this->isActive,
            ];
            if ($this->password !== '') {
                $data['password'] = $this->password;
            }
            $user->update($data);
            $user->syncRoles($this->role);
            session()->flash('success', "Account \"{$user->name}\" updated.");
        } else {
            $user = User::create([
                'name'          => $this->name,
                'employee_code' => $this->employeeCode,
                'role'          => $this->role,
                'is_active'     => $this->isActive,
                'password'      => $this->password,
                'email'         => null,
            ]);
            $user->syncRoles($this->role);
            session()->flash('success', "Account \"{$user->name}\" created.");
        }

        $this->showModal = false;
        unset($this->users);
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }
        $user->update(['is_active' => ! $user->is_active]);
        unset($this->users);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        $user->delete();
        session()->flash('success', "Account \"{$user->name}\" deleted.");
        unset($this->users);
    }

    public function render()
    {
        return view('livewire.admin.user-manager');
    }
}
