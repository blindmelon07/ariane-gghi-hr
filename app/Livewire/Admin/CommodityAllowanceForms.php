<?php

namespace App\Livewire\Admin;

use App\Models\CommodityAllowanceForm;
use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/** HR view of the Commodity Allowance Forms: who has submitted, who still has to. */
class CommodityAllowanceForms extends Component
{
    use WithPagination;

    public string $filter = 'all';   // all, submitted, missing
    public string $search = '';

    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function counts(): array
    {
        $regular   = Employee::where('is_active', true)->where('employment_type', 'regular')->count();
        $submitted = CommodityAllowanceForm::whereHas('employee', fn ($q) => $q->where('is_active', true)->where('employment_type', 'regular'))->count();

        return ['regular' => $regular, 'submitted' => $submitted, 'missing' => max(0, $regular - $submitted)];
    }

    #[Computed]
    public function employees()
    {
        return Employee::with('commodityAllowanceForm')
            ->where('is_active', true)
            ->where('employment_type', 'regular')
            ->when($this->filter === 'submitted', fn ($q) => $q->has('commodityAllowanceForm'))
            ->when($this->filter === 'missing', fn ($q) => $q->doesntHave('commodityAllowanceForm'))
            ->when($this->search, fn ($q) => $q->where(fn ($eq) => $eq
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('emp_code', 'like', "%{$this->search}%")))
            ->orderByFullName()
            ->paginate(25);
    }

    public function render()
    {
        return view('livewire.admin.commodity-allowance-forms');
    }
}
