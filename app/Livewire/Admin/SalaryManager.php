<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\SalaryDetail;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SalaryManager extends Component
{
    use WithPagination;

    public string $search = '';

    // Inline edit
    public ?int   $editingId      = null;
    public string $rateType       = 'monthly';
    public string $basicSalary    = '';
    public string $dailyRate      = '';
    public string $hourlyRate     = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return Employee::with('salaryDetail')
            ->where('is_active', true)
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('emp_code', 'like', "%{$this->search}%");
            }))
            ->orderBy('last_name')
            ->paginate(20);
    }

    public function edit(int $employeeId): void
    {
        $this->editingId = $employeeId;
        $salary = SalaryDetail::where('employee_id', $employeeId)->first();

        if ($salary) {
            $this->rateType    = $salary->rate_type;
            $this->basicSalary = (string) $salary->basic_salary;
            $this->dailyRate   = (string) $salary->daily_rate;
            $this->hourlyRate  = (string) $salary->hourly_rate;
        } else {
            $this->rateType    = 'monthly';
            $this->basicSalary = '';
            $this->dailyRate   = '';
            $this->hourlyRate  = '';
        }
    }

    public function updatedBasicSalary(): void
    {
        $this->autoComputeRates();
    }

    public function autoComputeRates(): void
    {
        $basic = (float) $this->basicSalary;

        if ($basic > 0) {
            // Assuming 26 working days/month (Mon-Sat), 8 hours/day
            $this->dailyRate  = (string) round($basic / 26, 2);
            $this->hourlyRate = (string) round($basic / 26 / 8, 2);
        }
    }

    public function save(): void
    {
        $this->validate([
            'basicSalary' => 'required|numeric|min:0',
            'dailyRate'   => 'required|numeric|min:0',
            'hourlyRate'  => 'required|numeric|min:0',
            'rateType'    => 'required|in:monthly,daily',
        ]);

        SalaryDetail::updateOrCreate(
            ['employee_id' => $this->editingId],
            [
                'rate_type'    => $this->rateType,
                'basic_salary' => $this->basicSalary,
                'daily_rate'   => $this->dailyRate,
                'hourly_rate'  => $this->hourlyRate,
            ],
        );

        $this->editingId = null;
        unset($this->employees);
        session()->flash('success', 'Salary details saved.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.salary-manager');
    }
}
