<?php

namespace App\Livewire\Admin;

use App\Models\DayOffSwap;
use App\Services\ActivityLogService;
use App\Services\DayOffSwapService;
use App\Services\LeaveService;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DayOffSwapApprovals extends Component
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

    /** Super admins can approve or reject at any step, without waiting for the others. */
    #[Computed]
    public function canOverride(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    #[Computed]
    public function requests()
    {
        return DayOffSwap::with('employee')
            ->when($this->filterStatus === 'pending', fn ($q) =>
                $q->where('status', 'pending')->when(! $this->canOverride, fn ($q) => $q->where('approval_step', $this->myStep))
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
            ->orderByDesc('original_date')
            ->paginate(20);
    }

    public function openAction(int $id, string $type): void
    {
        $this->selectedId = $id;
        $this->actionType = $type;
        $this->remarks    = '';
        $this->resetErrorBag();
        $this->dispatch('open-swap-modal');
    }

    public function confirmAction(): void
    {
        $swap = DayOffSwap::with('employee')->find($this->selectedId);
        if (! $swap || $swap->status !== 'pending') return;

        if ($swap->approval_step !== $this->myStep && ! $this->canOverride) {
            $this->addError('remarks', 'This request is not at your approval step.');
            return;
        }

        $steps    = LeaveService::APPROVAL_STEPS;
        $user     = Auth::user();
        $notifier = app(SmsNotificationService::class);
        $what     = 'rest day swap (' . $swap->original_date->format('M d') . ' → ' . $swap->new_date->format('M d, Y') . ')';
        $dates    = "{$swap->original_date->format('M d, Y')} → {$swap->new_date->format('M d, Y')}";

        if ($this->actionType === 'approve') {
            $nextStep = $swap->approval_step + 1;
            $override = $this->canOverride && $swap->approval_step !== $this->myStep;
            $isFinal  = ! isset($steps[$nextStep]) || $this->canOverride;   // super admin approves outright

            if ($isFinal) {
                // Schedules may have changed since the request was filed
                $service = app(DayOffSwapService::class);
                $error   = $service->validationError(
                    $swap->employee,
                    $swap->original_date->toDateString(),
                    $swap->new_date->toDateString(),
                    $swap->id
                );

                if ($error) {
                    $this->addError('remarks', 'Cannot approve: ' . reset($error) . ' Reject the request instead.');
                    $this->dispatch('open-swap-modal');
                    return;
                }

                $service->apply($swap, $user);
                $swap->update([
                    'status'        => 'approved',
                    'approval_step' => null,
                    'approved_by'   => $user->id,
                    'approved_at'   => now(),
                    'remarks'       => $this->remarks ?: null,
                ]);
                ActivityLogService::log('day_off_swap_approved', "Rest day swap approved" . ($override ? ' (Super Admin override)' : '') . " for {$swap->employee->full_name}: {$dates}", $swap->employee);
                $message = $override ? 'Rest day swap approved (Super Admin override).' : 'Rest day swap fully approved.';
                $notifier->requestDecision($swap->employee, $what, true, $this->remarks ?: null, route('day-off-swap.request'), "day-off-swap:{$swap->id}");
            } else {
                $swap->update(['approval_step' => $nextStep]);
                ActivityLogService::log('day_off_swap_step', "Step {$swap->approval_step} approved rest day swap for {$swap->employee->full_name}", $swap->employee);
                $message = 'Approved — forwarded to ' . $steps[$nextStep]['label'] . '.';
                $notifier->requestProgress($swap->employee, $what, $steps[$nextStep - 1]['label'], $steps[$nextStep]['label'], route('day-off-swap.request'), "day-off-swap:{$swap->id}");
            }

            $this->dispatch('toast', message: $message);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                $this->dispatch('open-swap-modal');
                return;
            }

            $swap->update([
                'status'        => 'rejected',
                'approval_step' => null,
                'approved_by'   => $user->id,
                'approved_at'   => now(),
                'remarks'       => $this->remarks,
            ]);
            ActivityLogService::log('day_off_swap_rejected', "Rest day swap rejected for {$swap->employee->full_name}: {$dates}", $swap->employee);
            $notifier->requestDecision($swap->employee, $what, false, $this->remarks, route('day-off-swap.request'), "day-off-swap:{$swap->id}");
            $this->dispatch('toast', message: 'Rest day swap rejected.');
        }

        $this->selectedId = null;
        $this->actionType = '';
        $this->remarks    = '';
        unset($this->requests, $this->myStep);
    }

    public function render()
    {
        return view('livewire.admin.day-off-swap-approvals');
    }
}
