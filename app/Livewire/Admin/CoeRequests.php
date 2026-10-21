<?php

namespace App\Livewire\Admin;

use App\Models\CoeRequest;
use App\Models\User;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/** HR queue for COE requests: release the COE, then record who received it. */
class CoeRequests extends Component
{
    use WithPagination;

    public string $filterStatus = 'open';   // open (pending + released), or a status, or all
    public string $search       = '';

    public ?int   $selectedId   = null;
    public string $action       = '';       // release, receive, reject
    public string $date         = '';
    public string $receivedBy   = '';
    public string $remarks      = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    #[Computed]
    public function requests()
    {
        return CoeRequest::with(['employee', 'releaser'])
            ->when($this->filterStatus === 'open', fn ($q) => $q->whereIn('status', ['pending', 'released']))
            ->when(! \in_array($this->filterStatus, ['open', 'all'], true), fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('emp_code', 'like', "%{$this->search}%")))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'released' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function openAction(int $id, string $action): void
    {
        $request = CoeRequest::with('employee')->find($id);
        if (! $request) return;

        $this->selectedId = $id;
        $this->action     = $action;
        $this->date       = now('Asia/Manila')->toDateString();
        $this->receivedBy = $request->employee->full_name;
        $this->remarks    = '';
        $this->resetErrorBag();
        $this->dispatch('open-coe-modal');
    }

    public function confirmAction(): void
    {
        $request = CoeRequest::with('employee')->find($this->selectedId);
        if (! $request) return;

        $employee = $request->employee;

        if ($this->action === 'release' && $request->status === 'pending') {
            $this->validate(['date' => 'required|date']);
            $request->update(['status' => 'released', 'released_by' => Auth::id(), 'released_at' => $this->date, 'remarks' => $this->remarks ?: null]);
            ActivityLogService::log('coe_released', "COE released for {$employee->full_name}", $employee);
            $this->notifyEmployee($request, 'Your COE is ready', 'Your Certificate of Employment is ready. Please pick it up at the HR office.');
            $message = 'COE marked as released.';
        } elseif ($this->action === 'receive' && $request->status === 'released') {
            $this->validate(['date' => 'required|date|after_or_equal:' . $request->released_at->toDateString(), 'receivedBy' => 'required|string|max:255']);
            $request->update(['status' => 'received', 'received_by_name' => $this->receivedBy, 'received_at' => $this->date]);
            ActivityLogService::log('coe_received', "COE received by {$this->receivedBy} for {$employee->full_name}", $employee);
            $message = 'COE receipt recorded.';
        } elseif ($this->action === 'reject' && $request->status === 'pending') {
            $this->validate(['remarks' => 'required|string|max:1000'], ['remarks.required' => 'Please give the reason for declining.']);
            $request->update(['status' => 'rejected', 'released_by' => Auth::id(), 'remarks' => $this->remarks]);
            ActivityLogService::log('coe_rejected', "COE request declined for {$employee->full_name}", $employee);
            $this->notifyEmployee($request, 'COE request declined', "Your COE request was declined. Reason: {$this->remarks}");
            $message = 'COE request declined.';
        } else {
            return;
        }

        $this->reset(['selectedId', 'action', 'date', 'receivedBy', 'remarks']);
        unset($this->requests);
        $this->dispatch('close-coe-modal');
        $this->dispatch('toast', message: $message);
    }

    private function notifyEmployee(CoeRequest $request, string $title, string $body): void
    {
        $user = User::where('employee_code', $request->employee->emp_code)->first();
        $user?->notify(new HrAlert($title, $body, route('coe.request'), 'request', "coe:{$request->id}:{$request->status}"));
    }

    public function render()
    {
        return view('livewire.admin.coe-requests');
    }
}
