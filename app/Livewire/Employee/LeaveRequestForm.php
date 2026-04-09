<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LeaveRequestForm extends Component
{
    #[Validate('required|exists:leave_types,id')]
    public $leave_type_id = '';

    #[Validate('required|date|after_or_equal:today')]
    public $start_date = '';

    #[Validate('required|date|after_or_equal:start_date')]
    public $end_date = '';

    #[Validate('required|string|min:10|max:1000')]
    public string $reason = '';

    #[Computed]
    public function totalDays(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return app(LeaveService::class)->computeTotalDays($this->start_date, $this->end_date);
    }

    #[Computed]
    public function remainingCredits(): float
    {
        if (!$this->leave_type_id) {
            return 0;
        }

        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();
        if (!$employee) {
            return 0;
        }

        return app(LeaveService::class)->getRemainingCredits(
            $employee->id,
            (int) $this->leave_type_id,
            now()->year
        );
    }

    public function updatedStartDate(): void
    {
        unset($this->totalDays, $this->remainingCredits);
    }

    public function updatedEndDate(): void
    {
        unset($this->totalDays, $this->remainingCredits);
    }

    public function updatedLeaveTypeId(): void
    {
        unset($this->remainingCredits);
    }

    public function submit(): void
    {
        $this->validate();

        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        $leaveService = app(LeaveService::class);
        $totalDays    = $leaveService->computeTotalDays($this->start_date, $this->end_date);

        // Check overlap
        if ($leaveService->hasOverlap($employee->id, $this->start_date, $this->end_date)) {
            $this->addError('start_date', 'You already have a pending or approved leave that overlaps with these dates.');
            return;
        }

        // Check credits
        $remaining = $leaveService->getRemainingCredits($employee->id, (int) $this->leave_type_id, now()->year);
        if ($totalDays > $remaining) {
            $this->addError('leave_type_id', "Insufficient credits. You have {$remaining} day(s) remaining.");
            return;
        }

        $request = LeaveRequest::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $this->leave_type_id,
            'start_date'    => $this->start_date,
            'end_date'      => $this->end_date,
            'total_days'    => $totalDays,
            'reason'        => $this->reason,
            'status'        => 'pending',
        ]);

        $leaveService->notifyAdmins($request);

        $this->reset(['leave_type_id', 'start_date', 'end_date', 'reason']);
        unset($this->totalDays, $this->remainingCredits);

        $this->dispatch('leave-filed');
        session()->flash('success', 'Leave request filed successfully.');
    }

    public function render()
    {
        return view('livewire.employee.leave-request-form', [
            'leaveTypes' => LeaveType::all(),
        ]);
    }
}
