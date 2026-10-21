<?php

namespace App\Livewire\Admin;

use App\Models\PatientTransport;
use Illuminate\Database\Eloquent\Model;

class PatientTransportApprovals extends StepApprovals
{
    /** HR approves patient transport slips (acting as Hospital Administrator). */
    public const STEPS = [
        1 => ['role' => 'hr_admin', 'label' => 'HR'],
    ];

    protected function model(): string { return PatientTransport::class; }
    protected function label(): string { return 'patient transport'; }
    protected function dateColumn(): string { return 'date'; }
    protected function employeeRoute(): string { return 'patient-transport.request'; }
    protected function steps(): array { return self::STEPS; }

    protected function describe(Model $request): string
    {
        return "patient transport slip {$request->series_no} ({$request->date->format('M d, Y')})";
    }

    public function details(Model $request): array
    {
        return [
            'Series No.'     => $request->series_no,
            'Date'           => $request->date->format('D, M d, Y'),
            'Patient'        => $request->patient_name,
            'Transported To' => $request->transported_to,
            'Ambulance'      => "{$request->plate_number} · {$request->driver_name}",
        ];
    }
}
