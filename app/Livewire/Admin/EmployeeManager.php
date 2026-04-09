<?php

namespace App\Livewire\Admin;

use App\Jobs\SyncEmployeesJob;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
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
        return Employee::query()
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

    public function render()
    {
        return view('livewire.admin.employee-manager');
    }
}
