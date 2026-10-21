<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\UndertimeRequest;
use App\Services\ActivityLogService;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class UndertimeForm extends Component
{
    /** How many days back an undertime that already happened can still be filed. */
    public const FILE_BACK_DAYS = 7;

    public string $date    = '';
    public string $timeOut = '';
    public string $reason  = '';

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    #[Computed]
    public function myRequests()
    {
        if (! $this->employee) return collect();

        return UndertimeRequest::where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $earliest = now()->subDays(self::FILE_BACK_DAYS)->toDateString();

        $this->validate([
            'date'    => "required|date|after_or_equal:{$earliest}",
            'timeOut' => 'required|date_format:H:i',
            'reason'  => 'required|string|min:10|max:1000',
        ], [
            'date.after_or_equal' => 'Undertime can only be filed up to ' . self::FILE_BACK_DAYS . ' days after the date.',
        ]);

        $emp = $this->employee;
        if (! $emp) {
            $this->addError('date', 'Employee record not found.');
            return;
        }

        $exists = UndertimeRequest::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('date', $this->date)
            ->exists();

        if ($exists) {
            $this->addError('date', 'You already have a pending or approved undertime request for this date.');
            return;
        }

        $request = UndertimeRequest::create([
            'employee_id'   => $emp->id,
            'date'          => $this->date,
            'time_out'      => $this->timeOut,
            'reason'        => $this->reason,
            'status'        => 'pending',
            'approval_step' => app(LeaveService::class)->getInitialStep(auth()->user()),
        ]);

        ActivityLogService::log('undertime_filed', "Undertime filed by {$emp->full_name}: {$request->date->format('M d, Y')} out at {$request->timeOutLabel()}", $emp);

        $this->reset(['date', 'timeOut', 'reason']);
        unset($this->myRequests);
        session()->flash('success', 'Undertime request submitted successfully.');
    }

    public function cancel(int $id): void
    {
        $request = UndertimeRequest::where('employee_id', $this->employee?->id)->find($id);
        if (! $request || $request->status !== 'pending') return;

        $request->update(['status' => 'cancelled', 'approval_step' => null]);
        unset($this->myRequests);
        session()->flash('success', 'Undertime request cancelled.');
    }

    public function render()
    {
        return view('livewire.employee.undertime-form');
    }
}
