<?php

namespace App\Livewire\Admin;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class LeaveApprovals extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $filterType   = '';
    public string $filterDept   = '';
    public string $search       = '';

    public ?int $selectedRequestId = null;
    public string $actionType      = '';
    public string $remarks         = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDept(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function myStep(): ?int
    {
        return app(LeaveService::class)->getStepForUser(Auth::user());
    }

    #[Computed]
    public function pendingRequests()
    {
        return LeaveRequest::with(['employee', 'leaveType', 'approvals.approver'])
            ->when($this->filterStatus === 'pending', function ($q) {
                // Each approver only sees requests waiting at their step
                $q->where('status', 'pending')
                  ->where('approval_step', $this->myStep);
            })
            ->when($this->filterStatus !== 'pending' && $this->filterStatus !== 'all',
                fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, fn ($q) => $q->where('leave_type_id', $this->filterType))
            ->when($this->filterDept, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('department', $this->filterDept)))
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('emp_code', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function openAction(int $id, string $type): void
    {
        $this->selectedRequestId = $id;
        $this->actionType        = $type;
        $this->remarks           = '';
        $this->dispatch('open-action-modal');
    }

    public function confirmAction(): void
    {
        $request = LeaveRequest::find($this->selectedRequestId);
        if (! $request || $request->status !== 'pending') {
            return;
        }

        $leaveService = app(LeaveService::class);
        $user         = Auth::user();

        // Guard: user can only act on their designated step
        if ($request->approval_step !== $this->myStep) {
            $this->addError('remarks', 'This request is not at your approval step.');
            return;
        }

        if ($this->actionType === 'approve') {
            $leaveService->approve($request, $user, $this->remarks ?: null);

            $nextStep = ($request->approval_step ?? 0) + 1;
            $message  = isset(LeaveService::APPROVAL_STEPS[$nextStep])
                ? 'Approved — forwarded to ' . LeaveService::APPROVAL_STEPS[$nextStep]['label'] . '.'
                : 'Leave fully approved.';
            $this->dispatch('toast', message: $message);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                return;
            }
            $leaveService->reject($request, $user, $this->remarks);
            $this->dispatch('toast', message: 'Leave request rejected.');
        }

        $this->selectedRequestId = null;
        $this->actionType        = '';
        $this->remarks           = '';
        unset($this->pendingRequests, $this->myStep);
    }

    public function render()
    {
        return view('livewire.admin.leave-approvals');
    }
}
