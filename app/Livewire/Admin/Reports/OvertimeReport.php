<?php

namespace App\Livewire\Admin\Reports;

use App\Models\OvertimeRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class OvertimeReport extends Component
{
    use WithPagination;

    public int    $year       = 0;
    public string $month      = '';
    public string $department = '';
    public string $status     = '';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function updatedYear(): void      { $this->resetPage(); }
    public function updatedMonth(): void     { $this->resetPage(); }
    public function updatedDepartment(): void { $this->resetPage(); }
    public function updatedStatus(): void    { $this->resetPage(); }

    #[Computed]
    public function departments(): array
    {
        return \App\Models\Employee::whereNotNull('department')
            ->where('is_active', true)
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->toArray();
    }

    private function baseQuery()
    {
        return OvertimeRequest::with(['employee', 'approver'])
            ->whereHas('employee')
            ->whereYear('date', $this->year)
            ->when($this->month, fn ($q) => $q->whereMonth('date', $this->month))
            ->when($this->department, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('department', $this->department)))
            ->when($this->status, fn ($q) => $q->where('status', $this->status));
    }

    #[Computed]
    public function summaryCards(): array
    {
        $base = $this->baseQuery();

        return [
            'total'            => (clone $base)->count(),
            'pending'          => (clone $base)->where('status', 'pending')->count(),
            'approved'         => (clone $base)->where('status', 'approved')->count(),
            'requested_hours'  => (float) (clone $base)->sum('requested_hours'),
            'approved_hours'   => (float) (clone $base)->where('status', 'approved')->sum('approved_hours'),
        ];
    }

    #[Computed]
    public function reportData()
    {
        return $this->baseQuery()
            ->orderByDesc('date')
            ->paginate(50);
    }

    public function render()
    {
        return view('livewire.admin.reports.overtime-report');
    }
}
