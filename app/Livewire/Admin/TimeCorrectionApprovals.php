<?php

namespace App\Livewire\Admin;

use App\Models\TimeCorrection;
use App\Services\ActivityLogService;
use App\Services\LeaveService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TimeCorrectionApprovals extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $search       = '';

    public ?int   $selectedId   = null;
    public string $actionType   = '';
    public string $remarks      = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    #[Computed]
    public function myStep(): ?int
    {
        return app(LeaveService::class)->getStepForUser(Auth::user());
    }

    #[Computed]
    public function requests()
    {
        return TimeCorrection::with('employee')
            ->when($this->filterStatus === 'pending', fn ($q) =>
                $q->where('status', 'pending')->where('approval_step', $this->myStep)
            )
            ->when($this->filterStatus !== 'pending' && $this->filterStatus !== 'all',
                fn ($q) => $q->where('status', $this->filterStatus)
            )
            ->when($this->search, fn ($q) =>
                $q->whereHas('employee', fn ($eq) =>
                    $eq->where('first_name', 'like', "%{$this->search}%")
                       ->orWhere('last_name', 'like', "%{$this->search}%")
                       ->orWhere('emp_code', 'like', "%{$this->search}%")
                )
            )
            ->orderByDesc('date')
            ->paginate(20);
    }

    public function openAction(int $id, string $type): void
    {
        $this->selectedId = $id;
        $this->actionType = $type;
        $this->remarks    = '';
        $this->dispatch('open-tc-modal');
    }

    public function confirmAction(): void
    {
        $tc = TimeCorrection::find($this->selectedId);
        if (! $tc || $tc->status !== 'pending') return;

        if ($tc->approval_step !== $this->myStep) {
            $this->addError('remarks', 'This request is not at your approval step.');
            return;
        }

        $steps = LeaveService::APPROVAL_STEPS;
        $user  = Auth::user();

        if ($this->actionType === 'approve') {
            $nextStep = $tc->approval_step + 1;
            $isFinal  = ! isset($steps[$nextStep]);

            if ($isFinal) {
                $tc->update([
                    'status'        => 'approved',
                    'approval_step' => null,
                    'approved_by'   => $user->id,
                    'approved_at'   => now(),
                    'remarks'       => $this->remarks ?: null,
                ]);
                ActivityLogService::log('time_correction_approved', "Time correction approved for {$tc->employee->full_name} on {$tc->date->format('M d, Y')}", $tc->employee);
                $message = 'Time correction fully approved.';
            } else {
                $tc->update(['approval_step' => $nextStep]);
                ActivityLogService::log('time_correction_step', "Step {$tc->approval_step} approved time correction for {$tc->employee->full_name}", $tc->employee);
                $message = 'Approved — forwarded to ' . $steps[$nextStep]['label'] . '.';
            }

            $this->dispatch('toast', message: $message);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                return;
            }

            $tc->update([
                'status'        => 'rejected',
                'approval_step' => null,
                'approved_by'   => $user->id,
                'approved_at'   => now(),
                'remarks'       => $this->remarks,
            ]);
            ActivityLogService::log('time_correction_rejected', "Time correction rejected for {$tc->employee->full_name} on {$tc->date->format('M d, Y')}", $tc->employee);
            $this->dispatch('toast', message: 'Time correction rejected.');
        }

        $this->selectedId = null;
        $this->actionType = '';
        $this->remarks    = '';
        unset($this->requests, $this->myStep);
    }

    public function render()
    {
        return view('livewire.admin.time-correction-approvals');
    }
}
