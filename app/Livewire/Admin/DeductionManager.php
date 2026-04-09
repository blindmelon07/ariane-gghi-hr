<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DeductionManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterDept = '';
    public string $filterStatus = 'active';

    // Add/Edit modal state
    public bool   $showModal       = false;
    public ?int   $editDeductionId = null;
    public ?int   $modalEmployeeId = null;
    public string $empSearch       = '';
    public string $description     = '';
    public string $amountPerCutoff = '';
    public string $remainingBalance = '';
    public bool   $isActive        = true;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterDept(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    #[Computed]
    public function deductions()
    {
        return OtherDeduction::with('employee')
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->whereHas('employee', fn ($e) =>
                    $e->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%")
                      ->orWhere('emp_code', 'like', "%{$this->search}%")
                )->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->filterDept, fn ($q) => $q->whereHas('employee', fn ($e) =>
                $e->where('department', $this->filterDept)
            ))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    #[Computed]
    public function departments()
    {
        return Employee::whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');
    }

    #[Computed]
    public function employeeResults()
    {
        if (strlen($this->empSearch) < 2) {
            return collect();
        }

        return Employee::where('is_active', true)
            ->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->empSearch}%")
                  ->orWhere('last_name', 'like', "%{$this->empSearch}%")
                  ->orWhere('emp_code', 'like', "%{$this->empSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function selectEmployee(int $id): void
    {
        $emp = Employee::find($id);
        if ($emp) {
            $this->modalEmployeeId = $emp->id;
            $this->empSearch = $emp->full_name . ' (' . $emp->emp_code . ')';
        }
    }

    public function openAdd(): void
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $deduction = OtherDeduction::with('employee')->findOrFail($id);
        $this->resetModal();

        $this->editDeductionId  = $deduction->id;
        $this->modalEmployeeId  = $deduction->employee_id;
        $this->empSearch        = $deduction->employee->full_name . ' (' . $deduction->employee->emp_code . ')';
        $this->description      = $deduction->description;
        $this->amountPerCutoff  = (string) $deduction->amount_per_cutoff;
        $this->remainingBalance = (string) $deduction->remaining_balance;
        $this->isActive         = $deduction->is_active;
        $this->showModal        = true;
    }

    public function save(): void
    {
        $this->validate([
            'modalEmployeeId'  => 'required|exists:employees,id',
            'description'      => 'required|string|max:255',
            'amountPerCutoff'  => 'required|numeric|min:0',
            'remainingBalance' => 'required|numeric|min:0',
        ]);

        $data = [
            'employee_id'      => $this->modalEmployeeId,
            'description'      => $this->description,
            'amount_per_cutoff' => $this->amountPerCutoff,
            'remaining_balance' => $this->remainingBalance,
            'is_active'        => $this->isActive,
        ];

        if ($this->editDeductionId) {
            $deduction = OtherDeduction::findOrFail($this->editDeductionId);
            $deduction->update($data);
            ActivityLogService::log('deduction_updated', "Updated deduction \"{$this->description}\" for employee #{$this->modalEmployeeId}.", $deduction);
        } else {
            $deduction = OtherDeduction::create($data);
            ActivityLogService::log('deduction_created', "Added deduction \"{$this->description}\" for employee #{$this->modalEmployeeId}.", $deduction);
        }

        $this->showModal = false;
        unset($this->deductions);
    }

    public function toggleActive(int $id): void
    {
        $deduction = OtherDeduction::findOrFail($id);
        $deduction->update(['is_active' => !$deduction->is_active]);
        $status = $deduction->is_active ? 'activated' : 'deactivated';
        ActivityLogService::log('deduction_toggled', "Deduction \"{$deduction->description}\" {$status}.", $deduction);
        unset($this->deductions);
    }

    private function resetModal(): void
    {
        $this->editDeductionId  = null;
        $this->modalEmployeeId  = null;
        $this->empSearch        = '';
        $this->description      = '';
        $this->amountPerCutoff  = '';
        $this->remainingBalance = '';
        $this->isActive         = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.deduction-manager');
    }
}
