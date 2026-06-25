<?php

namespace App\Livewire\Employee;

use App\Models\Driver;
use App\Models\Employee;
use App\Models\TripTicket;
use App\Models\Vehicle;
use App\Services\TripTicketService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TripTicketForm extends Component
{
    #[Validate('required|string|max:150')]
    public string $destination_from = '';

    #[Validate('required|string|max:150')]
    public string $destination_to = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $departure_datetime = '';

    #[Validate('nullable|date|after_or_equal:departure_datetime')]
    public string $return_datetime = '';

    #[Validate('nullable|exists:vehicles,id')]
    public string $vehicle_id = '';

    #[Validate('nullable|exists:drivers,id')]
    public string $driver_id = '';

    #[Validate('nullable|string|max:500')]
    public string $passengers = '';

    #[Validate('required|string|min:10|max:1000')]
    public string $purpose = '';

    #[Computed]
    public function availableVehicles()
    {
        return Vehicle::where('is_active', true)
            ->where('status', 'available')
            ->orderBy('make')
            ->get();
    }

    #[Computed]
    public function availableDrivers()
    {
        return Driver::where('is_active', true)
            ->with('employee')
            ->get();
    }

    public function submit(): void
    {
        $this->validate();

        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (! $employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        TripTicket::create([
            'employee_id'        => $employee->id,
            'vehicle_id'         => $this->vehicle_id ?: null,
            'driver_id'          => $this->driver_id ?: null,
            'department'         => $employee->department,
            'destination_from'   => $this->destination_from,
            'destination_to'     => $this->destination_to,
            'departure_datetime' => $this->departure_datetime,
            'return_datetime'    => $this->return_datetime ?: null,
            'passengers'         => $this->passengers ?: null,
            'purpose'            => $this->purpose,
            'status'             => 'pending',
            'approval_step'      => app(TripTicketService::class)->getInitialStep(auth()->user()),
        ]);

        $this->reset(['destination_from', 'destination_to', 'departure_datetime', 'return_datetime', 'vehicle_id', 'driver_id', 'passengers', 'purpose']);
        unset($this->availableVehicles, $this->availableDrivers);

        session()->flash('success', 'Trip ticket filed successfully.');
        $this->dispatch('trip-ticket-filed');
    }

    public function render()
    {
        return view('livewire.employee.trip-ticket-form');
    }
}
