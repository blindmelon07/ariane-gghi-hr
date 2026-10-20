<?php

namespace App\Livewire\Employee;

use App\Models\DayOffSwap;
use App\Models\Employee;
use App\Services\ActivityLogService;
use App\Services\DayOffSwapService;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DayOffSwapForm extends Component
{
    public string $originalDate = '';
    public string $newDate      = '';
    public string $reason       = '';

    public function mount(): void
    {
        // Allow deep-linking from the attendance calendar, e.g.
        // /day-off-swap?date=2026-10-24
        if ($date = request()->query('date')) {
            $this->originalDate = $date;
        }
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    #[Computed]
    public function restDays(): array
    {
        return $this->employee ? app(DayOffSwapService::class)->upcomingRestDays($this->employee) : [];
    }

    #[Computed]
    public function myRequests()
    {
        if (! $this->employee) return collect();

        return DayOffSwap::where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'originalDate' => 'required|date|after_or_equal:today',
            'newDate'      => 'required|date|after_or_equal:today|different:originalDate',
            'reason'       => 'required|string|min:10|max:1000',
        ], [
            'newDate.different' => 'The new rest day must be a different date.',
        ]);

        $emp = $this->employee;
        if (! $emp) {
            $this->addError('originalDate', 'Employee record not found.');
            return;
        }

        if ($error = app(DayOffSwapService::class)->validationError($emp, $this->originalDate, $this->newDate)) {
            $this->addError(array_key_first($error), reset($error));
            return;
        }

        $swap = DayOffSwap::create([
            'employee_id'   => $emp->id,
            'original_date' => $this->originalDate,
            'new_date'      => $this->newDate,
            'reason'        => $this->reason,
            'status'        => 'pending',
            'approval_step' => app(LeaveService::class)->getInitialStep(auth()->user()),
        ]);

        ActivityLogService::log('day_off_swap_filed', "Rest day swap filed by {$emp->full_name}: {$swap->original_date->format('M d, Y')} → {$swap->new_date->format('M d, Y')}", $emp);

        $this->reset(['originalDate', 'newDate', 'reason']);
        unset($this->myRequests);
        session()->flash('success', 'Rest day swap request submitted successfully.');
    }

    public function cancel(int $id): void
    {
        $swap = DayOffSwap::where('employee_id', $this->employee?->id)->find($id);
        if (! $swap || $swap->status !== 'pending') return;

        $swap->update(['status' => 'cancelled', 'approval_step' => null]);
        unset($this->myRequests);
        session()->flash('success', 'Rest day swap request cancelled.');
    }

    public function render()
    {
        return view('livewire.employee.day-off-swap-form');
    }
}
