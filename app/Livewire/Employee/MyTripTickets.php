<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\TripTicket;
use App\Services\TripTicketService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MyTripTickets extends Component
{
    use WithPagination;

    public string $filterStatus = 'all';

    public ?int $cancelId = null;

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tickets()
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (! $employee) {
            return TripTicket::where('id', 0)->paginate(10);
        }

        return TripTicket::with(['vehicle', 'driver.employee', 'approvals.approver'])
            ->where('employee_id', $employee->id)
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function confirmCancel(int $id): void
    {
        $this->cancelId = $id;
    }

    public function cancelTicket(): void
    {
        $ticket = TripTicket::find($this->cancelId);

        if ($ticket) {
            $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();
            if ($ticket->employee_id === $employee?->id) {
                app(TripTicketService::class)->cancel($ticket);
                $this->dispatch('toast', message: 'Trip ticket cancelled.');
            }
        }

        $this->cancelId = null;
        unset($this->tickets);
    }

    public function render()
    {
        return view('livewire.employee.my-trip-tickets');
    }
}
