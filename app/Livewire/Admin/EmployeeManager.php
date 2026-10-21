<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncEmployeesJob;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
    public string  $editEmpCode     = '';
    public string  $editFirstName   = '';
    public string  $editLastName    = '';
    public string  $editCellNumber  = '';
    public ?int    $editDepartmentId = null;
    public ?int    $editPositionId      = null;
    public string   $editEmploymentType  = 'regular';
    public ?int     $editWeekdayOff      = 6; // 1=Monday, 6=Saturday, null=none (Probationary)
    public string   $editDob            = '';
    public string   $editRegularizationDate = '';
    public bool     $editIsActive       = true;

    // Account modal state
    public bool    $showAccountModal = false;
    public ?int    $accountEmployeeId = null;
    public string  $accountEmpCode   = '';
    public string  $accountName      = '';
    public string  $accountPassword  = '';
    public string  $accountNewUsername = '';
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

    public function updatedEditDepartmentId(): void
    {
        $this->editPositionId = null;
        unset($this->positions);
    }

    public function updatedEditEmploymentType(): void
    {
        // Auto-set sensible weekday_off default when type changes
        if ($this->editEmploymentType === 'probationary') {
            $this->editWeekdayOff = null;
        } elseif ($this->editWeekdayOff === null) {
            $this->editWeekdayOff = 6; // Saturday
        }
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return Employee::with(['user', 'dept', 'pos'])
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filterDept, fn ($q) => $q->where('department_id', $this->filterDept))
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('emp_code', 'like', "%{$this->search}%");
            }))
            ->orderByFullName()
            ->paginate(25);
    }

    #[Computed]
    public function departments()
    {
        return Department::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function positions()
    {
        return Position::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $this->editDepartmentId);
            })
            ->orderBy('name')
            ->get();
    }

    public function openEdit(int $id): void
    {
        $emp = Employee::findOrFail($id);

        $this->editEmployeeId      = $emp->id;
        $this->editEmpCode         = $emp->emp_code;
        $this->editFirstName       = $emp->first_name;
        $this->editLastName        = $emp->last_name;
        $this->editCellNumber      = $emp->cell_number ?? '';
        $this->editDepartmentId    = $emp->department_id;
        $this->editPositionId      = $emp->position_id;
        $this->editEmploymentType  = $emp->employment_type ?? 'regular';
        $this->editWeekdayOff      = $emp->weekday_off;
        $this->editDob        = $emp->date_of_birth?->format('Y-m-d') ?? '';
        $this->editRegularizationDate = $emp->regularization_date?->format('Y-m-d') ?? '';
        $this->editIsActive   = $emp->is_active;
        $this->showEdit       = true;
    }

    public function saveEmployee(): void
    {
        $emp = Employee::findOrFail($this->editEmployeeId);

        $this->validate([
            // Editable so HR can correct it to match the biometric device's
            // enrolled user ID — attendance sync links punches to employees
            // purely by emp_code (see ZKTecoService::syncAttendance/syncUsers).
            'editEmpCode'        => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'emp_code')->ignore($emp->id),
                Rule::unique('users', 'employee_code')->ignore($emp->user_id),
            ],
            'editFirstName'      => 'required|string|max:255',
            'editLastName'       => 'required|string|max:255',
            'editCellNumber'     => 'nullable|string|max:20',
            'editDepartmentId'   => 'nullable|exists:departments,id',
            'editPositionId'     => 'nullable|exists:positions,id',
            'editEmploymentType' => 'required|in:probationary,regular',
            'editWeekdayOff'     => 'nullable|integer|in:1,6',
            'editDob'            => 'nullable|date',
            'editRegularizationDate' => 'nullable|date',
        ]);

        $emp->update([
            'emp_code'        => $this->editEmpCode,
            'first_name'      => $this->editFirstName,
            'last_name'       => $this->editLastName,
            'cell_number'     => $this->editCellNumber ?: null,
            'department_id'   => $this->editDepartmentId,
            'position_id'     => $this->editPositionId,
            'employment_type' => $this->editEmploymentType,
            'weekday_off'     => $this->editEmploymentType === 'probationary' ? null : $this->editWeekdayOff,
            'date_of_birth'   => $this->editDob ?: null,
            'regularization_date' => $this->editEmploymentType === 'regular' ? ($this->editRegularizationDate ?: null) : null,
            'is_active'       => $this->editIsActive,
        ]);

        // Keep the linked login account's username in lockstep — the whole
        // employee self-service side of the app looks up Employee by
        // auth()->user()->employee_code, so these two must never drift apart.
        if ($emp->user) {
            $emp->user->update(['employee_code' => $this->editEmpCode]);
        }

        ActivityLogService::log('employee_updated', "Updated employee: {$emp->full_name} ({$emp->emp_code})", $emp);

        $this->showEdit = false;
        unset($this->employees);
        session()->flash('success', 'Employee updated.');
    }

    public function cancelEdit(): void
    {
        $this->showEdit = false;
        $this->resetValidation();
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

        $this->accountEmployeeId   = $emp->id;
        $this->accountEmpCode      = $emp->emp_code;
        $this->accountName         = $emp->full_name;
        $this->accountPassword     = '';
        $this->accountNewUsername  = $emp->user?->username ?? '';
        $this->hasExistingAccount  = (bool) $emp->user;

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
            'accountRole'     => 'required|in:employee,hr_admin,manager,approver,super_admin',
        ]);

        $emp = Employee::findOrFail($this->accountEmployeeId);

        // Check if user already exists with this emp code
        $existingUser = User::where('employee_code', $emp->emp_code)->first();
        if ($existingUser) {
            // Link existing user and sync Spatie role
            $emp->update(['user_id' => $existingUser->id]);
            $existingUser->update(['role' => $this->accountRole, 'is_active' => true]);
            $existingUser->syncRoles($this->accountRole);
        } else {
            $user = User::create([
                'name'          => $emp->full_name,
                'employee_code' => $emp->emp_code,
                'password'      => Hash::make($this->accountPassword),
                'role'          => $this->accountRole,
                'is_active'     => true,
            ]);

            $user->syncRoles($this->accountRole);  // ← assign Spatie role
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

    public function changeUsername(): void
    {
        $emp = Employee::with('user')->findOrFail($this->accountEmployeeId);

        if (! $emp->user) {
            return;
        }

        // Sets the optional username only. employee_code links the account to
        // the employee record, so changing it would orphan their attendance,
        // leave and payslips.
        $this->accountNewUsername = Str::lower(trim($this->accountNewUsername));

        $this->validate(
            ['accountNewUsername' => User::usernameRules($emp->user->id, required: false)],
            ['accountNewUsername.regex' => 'Use only letters, numbers, dots, dashes and underscores.'],
        );

        $old = $emp->user->username ?? '(none)';
        $emp->user->update(['username' => $this->accountNewUsername ?: null]);
        ActivityLogService::log('username_changed', "Changed username from {$old} to " . ($this->accountNewUsername ?: '(none)') . " for {$emp->full_name}", $emp);
        $this->showAccountModal = false;
        unset($this->employees);
        session()->flash('success', $this->accountNewUsername
            ? "Username updated for {$emp->full_name}. They can sign in with {$this->accountNewUsername} or {$emp->user->employee_code}."
            : "Username removed for {$emp->full_name}. They sign in with {$emp->user->employee_code}.");
    }

    public function updateRole(): void
    {
        $this->validate([
            'accountRole' => 'required|in:employee,hr_admin,manager,approver,super_admin',
        ]);

        $emp = Employee::with('user')->findOrFail($this->accountEmployeeId);

        if ($emp->user) {
            $emp->user->update(['role' => $this->accountRole]);
            $emp->user->syncRoles($this->accountRole);  // ← sync Spatie role
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
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.employee-manager');
    }
}
