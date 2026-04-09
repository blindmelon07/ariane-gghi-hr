<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LeaveBalanceCard extends Component
{
    #[Computed]
    public function balances(): array
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            return [];
        }

        return LeaveCredit::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get()
            ->map(fn ($credit) => [
                'name'      => $credit->leaveType->name,
                'code'      => $credit->leaveType->code,
                'total'     => (float) $credit->total_credits,
                'used'      => (float) $credit->used_credits,
                'remaining' => (float) $credit->remaining_credits,
                'percent'   => $credit->total_credits > 0
                    ? round(($credit->remaining_credits / $credit->total_credits) * 100)
                    : 0,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.employee.leave-balance-card');
    }
}
