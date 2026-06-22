<?php

namespace App\Livewire\Admin;

use App\Models\OvertimeRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class OvertimeApprovals extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $search       = '';

    public ?int   $selectedId    = null;
    public string $actionType    = '';
    public string $approvedHours = '';
    public string $remarks       = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return OvertimeRequest::with('employee')
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) =>
                $eq->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('emp_code', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('date')
            ->paginate(15);
    }

    public function openAction(int $id, string $type): void
    {
        $request             = OvertimeRequest::find($id);
        $this->selectedId    = $id;
        $this->actionType    = $type;
        $this->approvedHours = $request ? (string) $request->requested_hours : '';
        $this->remarks       = '';
        $this->dispatch('open-action-modal');
    }

    public function confirmAction(): void
    {
        $request = OvertimeRequest::find($this->selectedId);
        if (!$request || $request->status !== 'pending') {
            return;
        }

        $type = $this->actionType;

        if ($type === 'approve') {
            $this->validate(['approvedHours' => 'required|numeric|min:0.5|max:8']);

            $request->update([
                'status'        => 'approved',
                'approved_by'   => auth()->id(),
                'approved_at'   => now(),
                'approved_hours' => $this->approvedHours,
                'remarks'       => $this->remarks ?: null,
            ]);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                return;
            }

            $request->update([
                'status'      => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'remarks'     => $this->remarks,
            ]);
        }

        $this->selectedId    = null;
        $this->actionType    = '';
        $this->approvedHours = '';
        $this->remarks       = '';
        unset($this->requests);

        session()->flash('success', $type === 'approve' ? 'OT request approved.' : 'OT request rejected.');
    }

    public function render()
    {
        return view('livewire.admin.overtime-approvals');
    }
}
