<?php

namespace App\Livewire\Employee;

use App\Models\CoeRequest;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CoeRequestForm extends Component
{
    public string $department        = '';
    public string $designation       = '';
    public string $dateHired         = '';
    public string $dateSeparated     = '';
    public string $reason            = '';
    public string $additionalDetails = '';
    public string $signatureName     = '';

    public function mount(): void
    {
        $this->prefill();
    }

    private function prefill(): void
    {
        $emp = $this->employee;
        $this->department    = (string) ($emp?->department ?? '');
        $this->designation   = (string) ($emp?->position ?? '');
        $this->dateHired     = $emp?->hire_date?->toDateString() ?? '';
        $this->signatureName = (string) ($emp?->full_name ?? '');
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    #[Computed]
    public function myRequests()
    {
        if (! $this->employee) return collect();

        return CoeRequest::where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'department'        => 'required|string|max:255',
            'designation'       => 'required|string|max:255',
            'dateHired'         => 'nullable|date|before_or_equal:today',
            'dateSeparated'     => 'nullable|date|after_or_equal:dateHired',
            'reason'            => 'required|string|min:5|max:1000',
            'additionalDetails' => 'nullable|string|max:1000',
            'signatureName'     => 'required|string|max:255',
        ], [], [
            'dateHired'         => 'date hired',
            'dateSeparated'     => 'date separated',
            'reason'            => 'reason for COE request',
            'signatureName'     => 'signature',
        ]);

        $employee = $this->employee;
        if (! $employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        $request = CoeRequest::create([
            'employee_id'        => $employee->id,
            'department'         => $this->department,
            'designation'        => $this->designation,
            'date_hired'         => $this->dateHired ?: null,
            'date_separated'     => $this->dateSeparated ?: null,
            'reason'             => $this->reason,
            'additional_details' => $this->additionalDetails ?: null,
            'signature_name'     => $this->signatureName,
            'status'             => 'pending',
        ]);

        ActivityLogService::log('coe_requested', "COE requested by {$employee->full_name}", $employee);

        $hr = User::where('role', 'hr_admin')->where('is_active', true)->get();
        Notification::send($hr, new HrAlert(
            'COE request — ' . $employee->full_name,
            "Reason: {$request->reason}",
            route('admin.coe-requests'),
            'request',
            "coe:{$request->id}:filed",
        ));

        $this->reset(['dateSeparated', 'reason', 'additionalDetails']);
        $this->prefill();
        unset($this->myRequests);
        session()->flash('success', 'COE request submitted. HR will let you know when it is ready for pickup.');
    }

    public function cancel(int $id): void
    {
        $request = CoeRequest::where('employee_id', $this->employee?->id)->find($id);

        if ($request && $request->status === 'pending') {
            $request->update(['status' => 'cancelled']);
            unset($this->myRequests);
            session()->flash('success', 'COE request cancelled.');
        }
    }

    public function render()
    {
        return view('livewire.employee.coe-request-form');
    }
}
