<?php

namespace App\Livewire\Admin\Reports;

use App\Exports\LeaveReportExport;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class LeaveReport extends Component
{
    use WithPagination;

    public int    $year        = 0;
    public string $leaveTypeId = '';
    public string $department  = '';
    public string $status      = '';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function updatedYear(): void { $this->resetPage(); }
    public function updatedLeaveTypeId(): void { $this->resetPage(); }
    public function updatedDepartment(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    #[Computed]
    public function leaveTypes()
    {
        return LeaveType::orderBy('name')->get();
    }

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

    #[Computed]
    public function reportData()
    {
        return LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->whereYear('start_date', $this->year)
            ->when($this->leaveTypeId, fn ($q) => $q->where('leave_type_id', $this->leaveTypeId))
            ->when($this->department, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('department', $this->department)))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(50);
    }

    #[Computed]
    public function summaryCards(): array
    {
        $base = LeaveRequest::where('status', 'approved')
            ->whereYear('start_date', $this->year);

        $vlType = LeaveType::where('code', 'VL')->first();
        $slType = LeaveType::where('code', 'SL')->first();
        $elType = LeaveType::where('code', 'EL')->first();

        return [
            'vl_used' => $vlType ? (float) (clone $base)->where('leave_type_id', $vlType->id)->sum('total_days') : 0,
            'sl_used' => $slType ? (float) (clone $base)->where('leave_type_id', $slType->id)->sum('total_days') : 0,
            'el_used' => $elType ? (float) (clone $base)->where('leave_type_id', $elType->id)->sum('total_days') : 0,
        ];
    }

    public function exportExcel()
    {
        $data = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->whereYear('start_date', $this->year)
            ->when($this->leaveTypeId, fn ($q) => $q->where('leave_type_id', $this->leaveTypeId))
            ->when($this->department, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('department', $this->department)))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->get();

        return Excel::download(
            new LeaveReportExport($data),
            'leave-report-' . $this->year . '.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.admin.reports.leave-report');
    }
}
