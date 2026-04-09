<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\Payslip;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyPayslips extends Component
{
    public int $year;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    #[Computed]
    public function payslips()
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            return collect();
        }

        return Payslip::with('payrollPeriod')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollPeriod', fn ($q) => $q->whereYear('start_date', $this->year))
            ->orderByDesc('created_at')
            ->get();
    }

    public function updatedYear(): void
    {
        unset($this->payslips);
    }

    public function render()
    {
        return view('livewire.employee.my-payslips');
    }
}
