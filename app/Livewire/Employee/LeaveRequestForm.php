<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveCredit;
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

    public bool $is_half_day = false;

    public function mount(): void
    {
        // Allow deep-linking from the attendance calendar, e.g.
        // /leave/request?date=2026-09-07&type=SL
        // Ignore anything that isn't a real Y-m-d date — a malformed value would
        // otherwise reach Carbon::parse() during render and 500 the page.
        $date = request()->query('date');
        if (is_string($date) && \Carbon\Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
            $this->start_date = $date;
            $this->end_date   = $date;
        }

        if ($code = request()->query('type')) {
            $leaveType = LeaveType::where('code', $code)->first();
            if ($leaveType) {
                $this->leave_type_id = $leaveType->id;
            }
        }
    }

    #[Computed]
    public function leaveBalances(): array
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();
        if (!$employee) {
            return [];
        }

        $leaveService = app(LeaveService::class);
        $year = now()->year;

        return LeaveType::all()->map(function ($type) use ($employee, $leaveService, $year) {
            $credit = LeaveCredit::where('employee_id', $employee->id)
                ->where('leave_type_id', $type->id)
                ->where('year', $year)
                ->first();

            $total = $credit?->total_credits ?? 0;
            $remaining = $leaveService->getRemainingCredits($employee->id, $type->id, $year);

            return [
                'name'      => $type->name,
                'code'      => $type->code,
                'total'     => $total,
                'used'      => $total - $remaining,
                'remaining' => $remaining,
            ];
        })->toArray();
    }

    #[Computed]
    public function totalDays(): float
    {
        if (!$this->start_date) {
            return 0;
        }

        if ($this->is_half_day) {
            return 0.5;
        }

        if (!$this->end_date) {
            return 0;
        }

        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        return app(LeaveService::class)->computeTotalDays($this->start_date, $this->end_date, $employee?->weekday_off, $employee?->id);
    }

    /** Days of this request covered by credits; the rest is unpaid. */
    #[Computed]
    public function paidDays(): float
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();
        if (! $employee || ! $this->leave_type_id) {
            return 0;
        }

        return app(LeaveService::class)->computePaidDays($employee->id, (int) $this->leave_type_id, now()->year, $this->totalDays);
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
        if ($this->is_half_day) {
            $this->end_date = $this->start_date;
        }
        unset($this->totalDays, $this->remainingCredits, $this->paidDays);
    }

    public function updatedEndDate(): void
    {
        unset($this->totalDays, $this->remainingCredits, $this->paidDays);
    }

    public function updatedLeaveTypeId(): void
    {
        unset($this->remainingCredits, $this->paidDays);
    }

    public function updatedIsHalfDay(): void
    {
        if ($this->is_half_day && $this->start_date) {
            $this->end_date = $this->start_date;
        }
        unset($this->totalDays, $this->paidDays);
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
        $totalDays    = $this->is_half_day
            ? 0.5
            : $leaveService->computeTotalDays($this->start_date, $this->end_date, $employee->weekday_off, $employee->id);

        // Check overlap
        if ($leaveService->hasOverlap($employee->id, $this->start_date, $this->end_date)) {
            $this->addError('start_date', 'You already have a pending or approved leave that overlaps with these dates.');
            return;
        }

        // No credit cap: days beyond the balance are filed unpaid. Recomputed at final approval.
        $paidDays = $leaveService->computePaidDays($employee->id, (int) $this->leave_type_id, now()->year, $totalDays);

        $request = LeaveRequest::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $this->leave_type_id,
            'start_date'    => $this->start_date,
            'end_date'      => $this->is_half_day ? $this->start_date : $this->end_date,
            'total_days'    => $totalDays,
            'paid_days'     => $paidDays,
            'is_half_day'   => $this->is_half_day,
            'reason'        => $this->reason,
            'status'        => 'pending',
            'approval_step' => $leaveService->getInitialStep(auth()->user()),
        ]);

        $leaveService->notifyAdmins($request);

        $this->reset(['leave_type_id', 'start_date', 'end_date', 'reason', 'is_half_day']);
        unset($this->totalDays, $this->remainingCredits, $this->paidDays);

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
