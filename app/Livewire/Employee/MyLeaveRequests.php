<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MyLeaveRequests extends Component
{
    public int $year;
    public string $status = 'all';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    #[On('leave-filed')]
    #[Computed]
    public function requests(): \Illuminate\Database\Eloquent\Collection
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $this->year)
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->get();
    }

    public function cancel(int $id): void
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();
        $request  = LeaveRequest::where('id', $id)->where('employee_id', $employee?->id)->first();

        if ($request) {
            app(LeaveService::class)->cancel($request);
            unset($this->requests);
            session()->flash('success', 'Leave request cancelled.');
        }
    }

    public function updatedStatus(): void
    {
        unset($this->requests);
    }

    public function updatedYear(): void
    {
        unset($this->requests);
    }

    public function render()
    {
        return view('livewire.employee.my-leave-requests');
    }
}
