<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ShiftChangeRequest;
use App\Services\ActivityLogService;
use App\Services\LeaveService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShiftChangeForm extends Component
{
    /** Longest range one request may cover; longer changes belong in Schedules (HR). */
    public const MAX_DAYS = 31;

    public string $startDate  = '';
    public string $endDate    = '';
    public ?int   $scheduleId = null;
    public string $reason     = '';

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    /** Active shifts open to everyone or to the employee's department. */
    #[Computed]
    public function shifts()
    {
        return Schedule::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('department')->orWhere('department', '')
                ->when($this->employee?->department, fn ($q, $dept) => $q->orWhere('department', $dept)))
            ->orderBy('time_in')
            ->get();
    }

    #[Computed]
    public function myRequests()
    {
        if (! $this->employee) return collect();

        return ShiftChangeRequest::with('schedule')
            ->where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'startDate'  => 'required|date|after_or_equal:today',
            'endDate'    => 'required|date|after_or_equal:startDate',
            'scheduleId' => 'required|integer',
            'reason'     => 'required|string|min:10|max:1000',
        ], [
            'endDate.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        $emp = $this->employee;
        if (! $emp) {
            $this->addError('startDate', 'Employee record not found.');
            return;
        }

        if (! $this->shifts->contains('id', $this->scheduleId)) {
            $this->addError('scheduleId', 'Select one of the available shifts.');
            return;
        }

        if (Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1 > self::MAX_DAYS) {
            $this->addError('endDate', 'A shift change can cover at most ' . self::MAX_DAYS . ' days. Ask HR to update your schedule for longer changes.');
            return;
        }

        $overlap = ShiftChangeRequest::where('employee_id', $emp->id)
            ->overlapping($this->startDate, $this->endDate)
            ->exists();

        if ($overlap) {
            $this->addError('startDate', 'You already have a pending or approved shift change on some of these dates.');
            return;
        }

        $request = ShiftChangeRequest::create([
            'employee_id'   => $emp->id,
            'start_date'    => $this->startDate,
            'end_date'      => $this->endDate,
            'schedule_id'   => $this->scheduleId,
            'reason'        => $this->reason,
            'status'        => 'pending',
            'approval_step' => app(LeaveService::class)->getInitialStep(auth()->user()),
        ]);

        ActivityLogService::log('shift_change_filed', "Shift change filed by {$emp->full_name}: {$request->start_date->format('M d, Y')} – {$request->end_date->format('M d, Y')}", $emp);

        $this->reset(['startDate', 'endDate', 'scheduleId', 'reason']);
        unset($this->myRequests);
        session()->flash('success', 'Shift change request submitted successfully.');
    }

    public function cancel(int $id): void
    {
        $request = ShiftChangeRequest::where('employee_id', $this->employee?->id)->find($id);
        if (! $request || $request->status !== 'pending') return;

        $request->update(['status' => 'cancelled', 'approval_step' => null]);
        unset($this->myRequests);
        session()->flash('success', 'Shift change request cancelled.');
    }

    public function render()
    {
        return view('livewire.employee.shift-change-form');
    }
}
