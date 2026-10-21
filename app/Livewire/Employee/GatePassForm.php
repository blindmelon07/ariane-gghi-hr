<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\GatePass;
use App\Models\User;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GatePassForm extends Component
{
    public string $date          = '';
    public string $timeOut       = '';
    public string $timeOfArrival = '';
    public string $destination   = '';
    public string $reason        = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    #[Computed]
    public function myPasses()
    {
        if (! $this->employee) return collect();

        return GatePass::where('employee_id', $this->employee->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'date'          => 'required|date|after_or_equal:today',
            'timeOut'       => 'required|date_format:H:i',
            'timeOfArrival' => 'required|date_format:H:i|after:timeOut',
            'destination'   => 'required|string|max:255',
            'reason'        => 'required|string|min:5|max:1000',
        ], [
            'timeOfArrival.after' => 'The time of arrival must be later than the time out.',
        ]);

        $emp = $this->employee;
        if (! $emp) {
            $this->addError('date', 'Employee record not found.');
            return;
        }

        $pass = GatePass::create([
            'employee_id'     => $emp->id,
            'date'            => $this->date,
            'time_out'        => $this->timeOut,
            'time_of_arrival' => $this->timeOfArrival,
            'destination'     => $this->destination,
            'reason'          => $this->reason,
            'status'          => 'pending',
            'approval_step'   => 1,
        ])->fresh();

        ActivityLogService::log('gate_pass_filed', "Gate pass {$pass->series_no} filed by {$emp->full_name} for {$pass->date->format('M d, Y')}", $emp);

        // Gate passes are usually same-day, so tell HR right away.
        $hr = User::where('role', 'hr_admin')->where('is_active', true)->get();
        Notification::send($hr, new HrAlert(
            'New gate pass — ' . $emp->full_name,
            "{$pass->series_no}: out {$pass->timeOutLabel()} to {$pass->destination}, back {$pass->timeOfArrivalLabel()} ({$pass->date->format('M d')}).",
            route('admin.gate-passes'),
            'request',
            "gate-pass:{$pass->id}:filed",
        ));

        $this->reset(['timeOut', 'timeOfArrival', 'destination', 'reason']);
        $this->date = now()->toDateString();
        unset($this->myPasses);
        session()->flash('success', "Gate pass {$pass->series_no} submitted. Show it to the guard once HR approves it.");
    }

    public function cancel(int $id): void
    {
        $pass = GatePass::where('employee_id', $this->employee?->id)->find($id);
        if (! $pass || $pass->status !== 'pending') return;

        $pass->update(['status' => 'cancelled', 'approval_step' => null]);
        unset($this->myPasses);
        session()->flash('success', 'Gate pass cancelled.');
    }

    public function render()
    {
        return view('livewire.employee.gate-pass-form');
    }
}
