<?php

namespace App\Livewire\Security;

use App\Models\PatientTransport;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Guard-on-duty log for patient transport slips: record departure once a
 * slip is approved, then arrival when the ambulance is back.
 */
class PatientTransportLog extends Component
{
    #[Computed]
    public function readyToDepart()
    {
        return PatientTransport::with('employee')
            ->where('status', 'approved')
            ->whereNull('departed_at')
            ->orderBy('date')
            ->get();
    }

    #[Computed]
    public function currentlyOut()
    {
        return PatientTransport::with('employee')
            ->where('status', 'approved')
            ->whereNotNull('departed_at')
            ->orderBy('departed_at')
            ->get();
    }

    #[Computed]
    public function recentArrivals()
    {
        return PatientTransport::where('status', 'completed')
            ->orderByDesc('arrived_at')
            ->limit(5)
            ->get();
    }

    public function depart(int $id): void
    {
        $slip = PatientTransport::where('status', 'approved')->whereNull('departed_at')->find($id);
        if (! $slip) return;

        $slip->update(['departed_at' => now(), 'departed_by' => Auth::id()]);
        ActivityLogService::log('patient_transport_departed', "Ambulance {$slip->plate_number} departed ({$slip->series_no})", $slip->employee);
        unset($this->readyToDepart, $this->currentlyOut);
        $this->dispatch('toast', message: "Departure recorded for {$slip->plate_number}.");
    }

    public function arrive(int $id): void
    {
        $slip = PatientTransport::where('status', 'approved')->whereNotNull('departed_at')->find($id);
        if (! $slip) return;

        $slip->update(['arrived_at' => now(), 'arrived_by' => Auth::id(), 'status' => 'completed']);
        ActivityLogService::log('patient_transport_arrived', "Ambulance {$slip->plate_number} arrived ({$slip->series_no})", $slip->employee);
        unset($this->currentlyOut, $this->recentArrivals);
        $this->dispatch('toast', message: "Arrival recorded for {$slip->plate_number}.");
    }

    public function render()
    {
        return view('livewire.security.patient-transport-log');
    }
}
