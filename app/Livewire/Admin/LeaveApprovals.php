<?php

namespace App\Livewire\Admin;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

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
    public function pendingRequests()
    {
        return LeaveRequest::with(['employee', 'leaveType'])
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
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
        if (!$request) {
            return;
        }

        $leaveService = app(LeaveService::class);

        if ($this->actionType === 'approve') {
            $leaveService->approve($request, auth()->user(), $this->remarks ?: null);
            $this->dispatch('toast', message: 'Leave request approved.');
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                return;
            }
            $leaveService->reject($request, auth()->user(), $this->remarks);
            $this->dispatch('toast', message: 'Leave request rejected.');
        }

        $this->selectedRequestId = null;
        $this->actionType        = '';
        $this->remarks           = '';
        unset($this->pendingRequests);
    }

    public function render()
    {
        return view('livewire.admin.leave-approvals');
    }
}
