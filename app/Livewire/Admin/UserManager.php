<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Login accounts. Super admins manage everyone; HR admins manage everyone
 * except super admins, and can't grant the super_admin role.
 */
class UserManager extends Component
{
    use WithPagination;

    public const ROLES = [
        'employee'        => 'Employee',
        'hr_admin'        => 'HR Admin',
        'manager'         => 'Manager',
        'department_head' => 'Department Head',
        'approver'        => 'Approver',
        'super_admin'     => 'Super Admin',
        'security_guard'  => 'Security Guard',
        'head_nurse'      => 'Head Nurse',
    ];

    public string $search = '';

    public bool   $showModal    = false;
    public ?int   $editId       = null;
    public string $name         = '';
    public string $employeeCode = '';
    public string $role         = 'employee';
    public bool   $isActive     = true;
    public string $password     = '';

    // "Pick from employees" when creating an account
    public ?int   $employeeId     = null;
    public string $employeeSearch = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('employee:id,user_id,emp_code,department')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('employee_code', 'like', "%{$this->search}%")
            ))
            ->orderBy('name')
            ->paginate(15);
    }

    /** Roles the current user may assign. */
    #[Computed]
    public function roleOptions(): array
    {
        return $this->isSuperAdmin()
            ? self::ROLES
            : array_diff_key(self::ROLES, ['super_admin' => true]);
    }

    /** Active employees who don't have a login account yet. */
    #[Computed]
    public function availableEmployees()
    {
        $taken = User::pluck('employee_code')->filter()->all();

        return Employee::query()
            ->where('is_active', true)
            ->whereNull('user_id')
            ->whereNotIn('emp_code', $taken)
            ->when($this->employeeSearch, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$this->employeeSearch}%")
                ->orWhere('last_name', 'like', "%{$this->employeeSearch}%")
                ->orWhere('emp_code', 'like', "%{$this->employeeSearch}%")
            ))
            ->orderBy('last_name')
            ->limit(50)
            ->get(['id', 'emp_code', 'first_name', 'last_name', 'full_name', 'department']);
    }

    #[Computed]
    public function pickedEmployee(): ?Employee
    {
        return $this->employeeId ? Employee::find($this->employeeId) : null;
    }

    public function openAdd(): void
    {
        $this->reset('editId', 'name', 'employeeCode', 'password', 'employeeId', 'employeeSearch');
        $this->role      = 'employee';
        $this->isActive  = true;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function pickEmployee(int $id): void
    {
        $emp = Employee::findOrFail($id);

        $this->employeeId   = $emp->id;
        $this->name         = $emp->full_name ?: trim("{$emp->first_name} {$emp->last_name}");
        $this->employeeCode = (string) $emp->emp_code;
        $this->resetErrorBag(['name', 'employeeCode']);
    }

    public function clearEmployee(): void
    {
        $this->reset('employeeId', 'name', 'employeeCode');
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        if (! $this->canManage($user)) {
            session()->flash('error', 'Only a super admin can edit super-admin accounts.');
            return;
        }

        $this->editId       = $id;
        $this->name         = $user->name;
        $this->employeeCode = $user->employee_code;
        $this->role         = $user->role;
        $this->isActive     = $user->is_active;
        $this->password     = '';
        $this->employeeId   = null;
        $this->resetErrorBag();
        $this->showModal    = true;
    }

    public function save(): void
    {
        $rules = [
            'name'         => 'required|string|max:100',
            'employeeCode' => 'required|string|max:20|unique:users,employee_code' . ($this->editId ? ",{$this->editId}" : ''),
            'role'         => 'required|in:' . implode(',', array_keys($this->roleOptions)),
            'isActive'     => 'boolean',
        ];

        if (! $this->editId) {
            $rules['password'] = 'required|string|min:6';
        } elseif ($this->password !== '') {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules, ['role.in' => 'You are not allowed to assign this role.']);

        if ($this->editId) {
            $user = User::findOrFail($this->editId);
            if (! $this->canManage($user)) {
                session()->flash('error', 'Only a super admin can edit super-admin accounts.');
                $this->showModal = false;
                return;
            }

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
            $linked = $this->linkEmployee($user);

            ActivityLogService::log('account_updated', "Updated account {$user->name} ({$user->employee_code})");
            session()->flash('success', "Account \"{$user->name}\" updated." . ($linked ? " Linked to employee {$linked->emp_code}." : ''));
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
            $linked = $this->linkEmployee($user);

            ActivityLogService::log('account_created', "Created account {$user->name} ({$user->employee_code}) as {$this->role}");
            session()->flash('success', "Account \"{$user->name}\" created. They log in with employee code {$user->employee_code}."
                . ($linked ? '' : ' Note: no employee record has this code, so this account won\'t get payslips, leave or notifications.'));
        }

        $this->showModal = false;
        unset($this->users, $this->availableEmployees);
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }
        if (! $this->canManage($user)) {
            session()->flash('error', 'Only a super admin can change super-admin accounts.');
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
        if (! $this->canManage($user)) {
            session()->flash('error', 'Only a super admin can delete super-admin accounts.');
            return;
        }

        Employee::where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();

        ActivityLogService::log('account_deleted', "Deleted account {$user->name} ({$user->employee_code})");
        session()->flash('success', "Account \"{$user->name}\" deleted.");
        unset($this->users, $this->availableEmployees);
    }

    public function canManage(User $user): bool
    {
        return $this->isSuperAdmin() || $user->role !== 'super_admin';
    }

    private function isSuperAdmin(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    /**
     * Link the account to its employee record: the one picked in the form, or
     * else the employee whose code matches. Returns the employee, if any.
     */
    private function linkEmployee(User $user): ?Employee
    {
        $employee = ($this->employeeId ? Employee::find($this->employeeId) : null)
            ?? Employee::where('user_id', $user->id)->first()
            ?? Employee::where('emp_code', $user->employee_code)->whereNull('user_id')->first();

        if ($employee && $employee->user_id !== $user->id) {
            $employee->update(['user_id' => $user->id]);
        }

        return $employee;
    }

    public function render()
    {
        return view('livewire.admin.user-manager');
    }
}
