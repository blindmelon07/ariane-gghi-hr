<?php

namespace App\Livewire\Employee;

use App\Models\Driver;
use App\Models\Employee;
use App\Models\PatientTransport;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PatientTransportForm extends Component
{
    public string $date          = '';
    public string $patientName   = '';
    public string $transportedTo = '';
    public string $plateNumber   = '';
    public string $driverName    = '';
    public string $medicalStaff  = '';

    public function mount(): void
    {
        abort_unless(PatientTransport::canFile($this->employee), 403);

        $this->date = now()->toDateString();
        if (mb_strtolower(trim((string) $this->employee->position)) === 'driver') {
            $this->driverName = $this->employee->full_name;
        }
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    /** Suggestions only — staff can still type a plate or driver not yet in Fleet. */
    #[Computed]
    public function ambulancePlates(): array
    {
        return Vehicle::where('vehicle_type', 'ambulance')->where('is_active', true)->orderBy('plate_number')->pluck('plate_number')->all();
    }

    #[Computed]
    public function driverNames(): array
    {
        return Driver::with('employee')->where('is_active', true)->get()
            ->map(fn ($d) => $d->employee?->full_name)->filter()->sort()->values()->all();
    }

    #[Computed]
    public function mySlips()
    {
        if (! $this->employee) return collect();

        return PatientTransport::where('employee_id', $this->employee->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        abort_unless(PatientTransport::canFile($this->employee), 403);

        $this->validate([
            'date'          => 'required|date|after_or_equal:today',
            'patientName'   => 'required|string|max:255',
            'transportedTo' => 'required|string|max:255',
            'plateNumber'   => 'required|string|max:30',
            'driverName'    => 'required|string|max:255',
            'medicalStaff'  => 'required|string|max:1000',
        ], [], [
            'patientName'   => "patient's name",
            'transportedTo' => 'transported to',
            'plateNumber'   => 'ambulance plate no.',
            'driverName'    => 'driver',
            'medicalStaff'  => 'names of medical staff',
        ]);

        $emp  = $this->employee;
        $slip = PatientTransport::create([
            'employee_id'    => $emp->id,
            'date'           => $this->date,
            'patient_name'   => $this->patientName,
            'transported_to' => $this->transportedTo,
            'plate_number'   => strtoupper(trim($this->plateNumber)),
            'driver_name'    => $this->driverName,
            'medical_staff'  => $this->medicalStaff,
            'status'         => 'pending',
            'approval_step'  => 1,
        ])->fresh();

        ActivityLogService::log('patient_transport_filed', "Patient transport {$slip->series_no} filed by {$emp->full_name}: {$slip->transported_to}", $emp);

        // The ambulance can't leave until HR approves, so alert HR right away.
        $hr = User::where('role', 'hr_admin')->where('is_active', true)->get();
        Notification::send($hr, new HrAlert(
            'Patient transport — approval needed',
            "{$slip->series_no}: {$slip->plate_number} to {$slip->transported_to}, filed by {$emp->full_name}.",
            route('admin.patient-transports'),
            'request',
            "patient-transport:{$slip->id}:filed",
        ));

        $this->reset(['patientName', 'transportedTo', 'medicalStaff']);
        $this->date = now()->toDateString();
        unset($this->mySlips);
        session()->flash('success', "Slip {$slip->series_no} submitted. The ambulance may leave once HR approves it.");
    }

    public function cancel(int $id): void
    {
        $slip = PatientTransport::where('employee_id', $this->employee?->id)->find($id);
        if (! $slip || $slip->status !== 'pending') return;

        $slip->update(['status' => 'cancelled', 'approval_step' => null]);
        unset($this->mySlips);
        session()->flash('success', 'Patient transport slip cancelled.');
    }

    public function render()
    {
        return view('livewire.employee.patient-transport-form');
    }
}
