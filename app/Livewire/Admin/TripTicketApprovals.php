<?php

namespace App\Livewire\Admin;

use App\Models\TripTicket;
use App\Services\TripTicketService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class TripTicketApprovals extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $filterDept   = '';
    public string $search       = '';

    public ?int $selectedId = null;
    public string $actionType = '';
    public string $remarks    = '';

    // For fleet step: optionally assign/change vehicle and driver
    public string $assignVehicleId = '';
    public string $assignDriverId  = '';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterDept(): void   { $this->resetPage(); }

    #[Computed]
    public function mySteps(): array
    {
        return app(TripTicketService::class)->getApplicableSteps(Auth::user());
    }

    /** Super admins can approve or reject at any step, without waiting for the others. */
    #[Computed]
    public function canOverride(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    #[Computed]
    public function tickets()
    {
        return TripTicket::with(['employee', 'vehicle', 'driver.employee', 'approvals.approver'])
            ->when($this->filterStatus === 'pending', function ($q) {
                $q->where('status', 'pending')
                  ->when(! $this->canOverride, fn ($q) => $q->whereIn('approval_step', $this->mySteps));
            })
            ->when($this->filterStatus !== 'pending' && $this->filterStatus !== 'all',
                fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterDept, fn ($q) => $q->where('department', $this->filterDept))
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('emp_code', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function openAction(int $id, string $type): void
    {
        $ticket = TripTicket::with(['vehicle', 'driver'])->find($id);
        $this->selectedId      = $id;
        $this->actionType      = $type;
        $this->remarks         = '';
        $this->assignVehicleId = $ticket?->vehicle_id ?? '';
        $this->assignDriverId  = $ticket?->driver_id ?? '';
        $this->resetErrorBag();
        $this->dispatch('open-action-modal');
    }

    public function confirmAction(): void
    {
        $ticket = TripTicket::find($this->selectedId);
        if (! $ticket || $ticket->status !== 'pending') {
            return;
        }

        $service = app(TripTicketService::class);
        $user    = Auth::user();

        if (! in_array($ticket->approval_step, $this->mySteps) && ! $this->canOverride) {
            $this->addError('remarks', 'This request is not at your approval step.');
            $this->dispatch('open-action-modal');
            return;
        }

        // HR / super admin tickets start at the Fleet step, which they also hold
        if ($user->employee_code && $ticket->employee?->emp_code === $user->employee_code) {
            $this->addError('remarks', 'You cannot approve or reject your own request.');
            $this->dispatch('open-action-modal');
            return;
        }

        // Fleet step (or a super admin approving outright): update vehicle/driver assignment if provided
        if ($ticket->approval_step === 3 || ($this->canOverride && $this->actionType === 'approve')) {
            $ticket->update([
                'vehicle_id' => $this->assignVehicleId ?: $ticket->vehicle_id,
                'driver_id'  => $this->assignDriverId ?: $ticket->driver_id,
            ]);
        }

        if ($this->actionType === 'approve' && $this->canOverride) {
            $skipped = ! in_array($ticket->approval_step, $this->mySteps);
            $service->overrideApprove($ticket->fresh(), $user, $this->remarks ?: null);
            $this->dispatch('toast', message: $skipped ? 'Trip ticket approved (Super Admin override).' : 'Trip ticket fully approved and scheduled.');
        } elseif ($this->actionType === 'approve') {
            $service->approve($ticket->fresh(), $user, $this->remarks ?: null);

            $nextStep = ($ticket->approval_step ?? 0) + 1;
            $message  = isset(TripTicketService::APPROVAL_STEPS[$nextStep])
                ? 'Approved — forwarded to ' . TripTicketService::APPROVAL_STEPS[$nextStep]['label'] . '.'
                : 'Trip ticket fully approved and scheduled.';
            $this->dispatch('toast', message: $message);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                $this->dispatch('open-action-modal');
                return;
            }
            $service->reject($ticket->fresh(), $user, $this->remarks);
            $this->dispatch('toast', message: 'Trip ticket rejected.');
        }

        $this->selectedId  = null;
        $this->actionType  = '';
        $this->remarks     = '';
        unset($this->tickets, $this->mySteps);
    }

    public function markReturned(int $id): void
    {
        $ticket = TripTicket::find($id);
        if (! $ticket || $ticket->status !== 'approved') {
            return;
        }

        app(TripTicketService::class)->complete($ticket, Auth::user());
        unset($this->tickets);
        $this->dispatch('toast', message: 'Vehicle marked as returned. Trip completed.');
    }

    public function render()
    {
        return view('livewire.admin.trip-ticket-approvals');
    }
}
