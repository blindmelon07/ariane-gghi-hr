<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OvertimeRequestForm extends Component
{
    public string $date           = '';
    public string $requestedHours = '';
    public string $reason         = '';

    public function mount(): void
    {
        $this->date = now()->addDay()->toDateString();
    }

    #[Computed]
    public function myRequests()
    {
        $employee = $this->resolveEmployee();
        if (!$employee) {
            return collect();
        }

        return OvertimeRequest::where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'date'           => 'required|date|after_or_equal:today',
            'requestedHours' => 'required|numeric|min:0.5|max:8',
            'reason'         => 'required|string|min:5|max:500',
        ]);

        $employee = $this->resolveEmployee();

        if (!$employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        $exists = OvertimeRequest::where('employee_id', $employee->id)
            ->where('date', $this->date)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            $this->addError('date', 'You already have a pending or approved OT request for this date.');
            return;
        }

        OvertimeRequest::create([
            'employee_id'     => $employee->id,
            'date'            => $this->date,
            'requested_hours' => $this->requestedHours,
            'reason'          => $this->reason,
            'status'          => 'pending',
        ]);

        $this->reset(['requestedHours', 'reason']);
        $this->date = now()->addDay()->toDateString();
        unset($this->myRequests);

        session()->flash('success', 'Overtime request submitted successfully.');
    }

    public function cancel(int $id): void
    {
        $employee = $this->resolveEmployee();
        $request  = OvertimeRequest::where('id', $id)
            ->where('employee_id', $employee?->id)
            ->first();

        if ($request && $request->status === 'pending') {
            $request->update(['status' => 'cancelled']);
            unset($this->myRequests);
            session()->flash('success', 'OT request cancelled.');
        }
    }

    private function resolveEmployee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    public function render()
    {
        return view('livewire.employee.overtime-request-form');
    }
}
