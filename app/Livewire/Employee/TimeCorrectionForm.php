<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\TimeCorrection;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TimeCorrectionForm extends Component
{
    public string  $date       = '';
    public string  $amTimeIn   = '';
    public string  $amTimeOut  = '';
    public string  $pmTimeIn   = '';
    public string  $pmTimeOut  = '';
    public string  $reason     = '';

    public function mount(): void
    {
        // Allow deep-linking from the attendance calendar, e.g.
        // /time-correction?date=2026-09-07
        if ($date = request()->query('date')) {
            $this->date = $date;
        }
    }

    #[Computed]
    public function myRequests()
    {
        $emp = Employee::where('emp_code', auth()->user()->employee_code)->first();
        if (! $emp) return collect();

        return TimeCorrection::where('employee_id', $emp->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'date'   => 'required|date|before_or_equal:today',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if (! $this->amTimeIn && ! $this->amTimeOut && ! $this->pmTimeIn && ! $this->pmTimeOut) {
            $this->addError('amTimeIn', 'Please provide at least one corrected time.');
            return;
        }

        $emp = Employee::where('emp_code', auth()->user()->employee_code)->first();
        if (! $emp) {
            $this->addError('date', 'Employee record not found.');
            return;
        }

        // Check if a pending correction already exists for this date
        $existing = TimeCorrection::where('employee_id', $emp->id)
            ->whereDate('date', $this->date)
            ->whereIn('status', ['pending'])
            ->exists();

        if ($existing) {
            $this->addError('date', 'You already have a pending time correction for this date.');
            return;
        }

        $initialStep = app(LeaveService::class)->getInitialStep(auth()->user());

        TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $this->date,
            'am_time_in'    => $this->amTimeIn  ?: null,
            'am_time_out'   => $this->amTimeOut ?: null,
            'pm_time_in'    => $this->pmTimeIn  ?: null,
            'pm_time_out'   => $this->pmTimeOut ?: null,
            'reason'        => $this->reason,
            'status'        => 'pending',
            'approval_step' => $initialStep,
        ]);

        $this->reset(['date', 'amTimeIn', 'amTimeOut', 'pmTimeIn', 'pmTimeOut', 'reason']);
        unset($this->myRequests);
        session()->flash('success', 'Time correction request submitted successfully.');
    }

    public function render()
    {
        return view('livewire.employee.time-correction-form');
    }
}
