<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncEmployeesJob;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeManager extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterDept   = '';
    public string $filterStatus = 'active';

    // Edit modal state
    public bool    $showEdit        = false;
    public ?int    $editEmployeeId  = null;
    public string  $editFirstName   = '';
    public string  $editLastName    = '';
    public string  $editDepartment  = '';
    public string  $editPosition    = '';
    public string  $editDob         = '';
    public bool    $editIsActive    = true;

    // Account modal state
    public bool    $showAccountModal = false;
    public ?int    $accountEmployeeId = null;
    public string  $accountEmpCode   = '';
    public string  $accountName      = '';
    public string  $accountPassword  = '';
    public string  $accountRole      = 'employee';
    public bool    $hasExistingAccount = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDept(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return Employee::with('user')
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filterDept, fn ($q) => $q->where('department', $this->filterDept))
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('emp_code', 'like', "%{$this->search}%");
            }))
            ->orderBy('last_name')
            ->paginate(25);
    }

    #[Computed]
    public function departments(): array
    {
        return Employee::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->toArray();
    }

    public function openEdit(int $id): void
    {
        $emp = Employee::findOrFail($id);

        $this->editEmployeeId = $emp->id;
        $this->editFirstName  = $emp->first_name;
        $this->editLastName   = $emp->last_name;
        $this->editDepartment = $emp->department ?? '';
        $this->editPosition   = $emp->position ?? '';
        $this->editDob        = $emp->date_of_birth?->format('Y-m-d') ?? '';
        $this->editIsActive   = $emp->is_active;
        $this->showEdit       = true;
    }

    public function saveEmployee(): void
    {
        $this->validate([
            'editFirstName'  => 'required|string|max:255',
            'editLastName'   => 'required|string|max:255',
            'editDepartment' => 'nullable|string|max:255',
            'editPosition'   => 'nullable|string|max:255',
            'editDob'        => 'nullable|date',
        ]);

        $emp = Employee::findOrFail($this->editEmployeeId);
        $emp->update([
            'first_name'    => $this->editFirstName,
            'last_name'     => $this->editLastName,
            'department'    => $this->editDepartment ?: null,
            'position'      => $this->editPosition ?: null,
            'date_of_birth' => $this->editDob ?: null,
            'is_active'     => $this->editIsActive,
        ]);

        ActivityLogService::log('employee_updated', "Updated employee: {$emp->full_name} ({$emp->emp_code})", $emp);

        $this->showEdit = false;
        unset($this->employees);
        session()->flash('success', 'Employee updated.');
    }

    public function cancelEdit(): void
    {
        $this->showEdit = false;
    }

    public function syncBioTime(): void
    {
        SyncEmployeesJob::dispatch();
        session()->flash('success', 'BioTime sync job dispatched. Employees will update shortly.');
    }

    // ── Account Management ───────────────────────────────

    public function openAccountModal(int $id): void
    {
        $emp = Employee::with('user')->findOrFail($id);

        $this->accountEmployeeId  = $emp->id;
        $this->accountEmpCode     = $emp->emp_code;
        $this->accountName        = $emp->full_name;
        $this->accountPassword    = '';
        $this->hasExistingAccount = (bool) $emp->user;

        if ($emp->user) {
            $this->accountRole = $emp->user->role;
        } else {
            $this->accountRole = 'employee';
        }

        $this->showAccountModal = true;
    }

    public function createAccount(): void
    {
        $this->validate([
            'accountPassword' => 'required|string|min:6',
            'accountRole'     => 'required|in:employee,hr_admin,manager',
        ]);

        $emp = Employee::findOrFail($this->accountEmployeeId);

        // Check if user already exists with this emp code
        $existingUser = User::where('employee_code', $emp->emp_code)->first();
        if ($existingUser) {
            // Link existing user
            $emp->update(['user_id' => $existingUser->id]);
            $existingUser->update(['role' => $this->accountRole, 'is_active' => true]);
        } else {
            $user = User::create([
                'name'          => $emp->full_name,
                'employee_code' => $emp->emp_code,
                'password'      => Hash::make($this->accountPassword),
                'role'          => $this->accountRole,
                'is_active'     => true,
            ]);

            $emp->update(['user_id' => $user->id]);
        }

        ActivityLogService::log('account_created', "Created account for {$emp->full_name} ({$emp->emp_code})", $emp);

        $this->showAccountModal = false;
        unset($this->employees);
        session()->flash('success', "Account created for {$emp->full_name}. Password: use the one you set.");
    }

    public function resetPassword(): void
    {
        $this->validate([
            'accountPassword' => 'required|string|min:6',
        ]);

        $emp = Employee::with('user')->findOrFail($this->accountEmployeeId);

        if ($emp->user) {
            $emp->user->update([
                'password' => Hash::make($this->accountPassword),
            ]);

            ActivityLogService::log('password_reset', "Reset password for {$emp->full_name} ({$emp->emp_code})", $emp);
            $this->showAccountModal = false;
            session()->flash('success', "Password reset for {$emp->full_name}.");
        }
    }

    public function updateRole(): void
    {
        $this->validate([
            'accountRole' => 'required|in:employee,hr_admin,manager',
        ]);

        $emp = Employee::with('user')->findOrFail($this->accountEmployeeId);

        if ($emp->user) {
            $emp->user->update(['role' => $this->accountRole]);
            ActivityLogService::log('role_changed', "Changed role to {$this->accountRole} for {$emp->full_name}", $emp);
            $this->showAccountModal = false;
            unset($this->employees);
            session()->flash('success', "Role updated to {$this->accountRole} for {$emp->full_name}.");
        }
    }

    public function toggleAccountActive(): void
    {
        $emp = Employee::with('user')->findOrFail($this->accountEmployeeId);

        if ($emp->user) {
            $emp->user->update(['is_active' => !$emp->user->is_active]);
            $status = $emp->user->fresh()->is_active ? 'activated' : 'deactivated';
            ActivityLogService::log('account_toggled', "Account {$status} for {$emp->full_name}", $emp);
            $this->showAccountModal = false;
            unset($this->employees);
            session()->flash('success', "Account {$status} for {$emp->full_name}.");
        }
    }

    public function closeAccountModal(): void
    {
        $this->showAccountModal = false;
    }

    public function render()
    {
        return view('livewire.admin.employee-manager');
    }
}
