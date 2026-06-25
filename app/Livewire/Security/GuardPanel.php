<?php

namespace App\Livewire\Security;

use App\Models\TripTicket;
use App\Services\TripTicketService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class GuardPanel extends Component
{
    use WithPagination;

    public string $search = '';

    // Shared modal state — action is either 'depart' or 'return'
    public ?int    $activeId      = null;
    public string  $activeAction  = '';     // 'depart' | 'return'

    #[Validate('nullable|string|max:500')]
    public string $return_remarks = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Approved tickets that haven't departed yet
    #[Computed]
    public function readyToDepart()
    {
        return TripTicket::with(['employee', 'vehicle', 'driver.employee'])
            ->where('status', 'approved')
            ->whereNull('departed_at')
            ->when($this->search, fn ($q) => $q
                ->whereHas('employee', fn ($eq) => $eq
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name',  'like', "%{$this->search}%")
                    ->orWhere('emp_code',   'like', "%{$this->search}%"))
                ->orWhereHas('vehicle', fn ($vq) => $vq
                    ->where('plate_number', 'like', "%{$this->search}%")))
            ->orderBy('departure_datetime')
            ->get();
    }

    // Approved tickets that have departed but not yet returned
    #[Computed]
    public function currentlyOut()
    {
        return TripTicket::with(['employee', 'vehicle', 'driver.employee'])
            ->where('status', 'approved')
            ->whereNotNull('departed_at')
            ->when($this->search, fn ($q) => $q
                ->whereHas('employee', fn ($eq) => $eq
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name',  'like', "%{$this->search}%")
                    ->orWhere('emp_code',   'like', "%{$this->search}%"))
                ->orWhereHas('vehicle', fn ($vq) => $vq
                    ->where('plate_number', 'like', "%{$this->search}%")))
            ->orderBy('departed_at')
            ->get();
    }

    #[Computed]
    public function recentReturns()
    {
        return TripTicket::with(['employee', 'vehicle', 'completedBy'])
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();
    }

    public function openAction(int $id, string $action): void
    {
        $this->activeId      = $id;
        $this->activeAction  = $action;
        $this->return_remarks = '';
        $this->dispatch('open-guard-modal');
    }

    public function confirmAction(): void
    {
        $this->validateOnly('return_remarks');

        $ticket  = TripTicket::find($this->activeId);
        $service = app(TripTicketService::class);
        $user    = Auth::user();

        if (! $ticket) {
            $this->reset(['activeId', 'activeAction', 'return_remarks']);
            return;
        }

        if ($this->activeAction === 'depart') {
            $service->depart($ticket, $user);
            $this->dispatch('toast', message: 'Vehicle departure confirmed.');
        } elseif ($this->activeAction === 'return') {
            $service->complete($ticket, $user, $this->return_remarks ?: null);
            $this->dispatch('toast', message: 'Vehicle marked as returned. Trip completed.');
        }

        $this->reset(['activeId', 'activeAction', 'return_remarks']);
        unset($this->readyToDepart, $this->currentlyOut, $this->recentReturns);
    }

    public function render()
    {
        return view('livewire.security.guard-panel');
    }
}
