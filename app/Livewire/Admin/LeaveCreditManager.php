<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveType;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveCreditManager extends Component
{
    use WithPagination;

    public int    $year;
    public string $search     = '';
    public string $filterDept = '';

    // Per-employee modal
    public bool  $showEmployeeModal = false;
    public ?int  $modalEmployeeId   = null;
    public string $modalEmployeeName = '';
    public array $modalCredits      = []; // [leave_type_id => ['total' => x, 'used' => y]]

    // Bulk add modal
    public bool   $showBulkModal    = false;
    public string $bulkLeaveTypeId  = '';
    public string $bulkCredits      = '';
    public string $bulkMode         = 'set'; // 'set' or 'add'

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterDept(): void { $this->resetPage(); }

    #[Computed]
    public function employees()
    {
        return Employee::with(['leaveCredits' => fn ($q) => $q->with('leaveType')->where('year', $this->year)])
            ->where('is_active', true)
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%")
                   ->orWhere('emp_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterDept, fn ($q) => $q->where('department', $this->filterDept))
            ->orderBy('last_name')
            ->paginate(25);
    }

    #[Computed]
    public function departments()
    {
        return Employee::whereNotNull('department')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('department')
            ->pluck('department');
    }

    #[Computed]
    public function leaveTypes()
    {
        return LeaveType::all();
    }

    // ── Per-Employee Modal ─────────────────────────────────

    public function openEmployeeCredits(int $employeeId): void
    {
        $employee = Employee::with(['leaveCredits' => fn ($q) => $q->where('year', $this->year)])
            ->findOrFail($employeeId);

        $this->modalEmployeeId   = $employee->id;
        $this->modalEmployeeName = $employee->full_name . ' (' . $employee->emp_code . ')';
        $this->modalCredits      = [];

        foreach ($this->leaveTypes as $type) {
            $credit = $employee->leaveCredits->firstWhere('leave_type_id', $type->id);
            $this->modalCredits[$type->id] = [
                'total' => $credit ? (string) $credit->total_credits : (string) $type->max_days_per_year,
                'used'  => $credit ? (string) $credit->used_credits : '0',
            ];
        }

        $this->showEmployeeModal = true;
    }

    public function saveEmployeeCredits(): void
    {
        $this->validate([
            'modalCredits.*.total' => 'required|numeric|min:0|max:365',
            'modalCredits.*.used'  => 'required|numeric|min:0|max:365',
        ]);

        foreach ($this->modalCredits as $typeId => $vals) {
            LeaveCredit::updateOrCreate(
                [
                    'employee_id'   => $this->modalEmployeeId,
                    'leave_type_id' => $typeId,
                    'year'          => $this->year,
                ],
                [
                    'total_credits' => $vals['total'],
                    'used_credits'  => $vals['used'],
                ]
            );
        }

        ActivityLogService::log('leave_credits_updated', "Updated leave credits for employee #{$this->modalEmployeeId} ({$this->year}).");

        $this->showEmployeeModal = false;
        unset($this->employees);
        session()->flash('success', "Credits updated for {$this->modalEmployeeName}.");
    }

    // ── Bulk Add Modal ─────────────────────────────────────

    public function openBulkAdd(): void
    {
        $this->bulkLeaveTypeId = '';
        $this->bulkCredits     = '';
        $this->bulkMode        = 'set';
        $this->showBulkModal   = true;
    }

    public function saveBulk(): void
    {
        $this->validate([
            'bulkLeaveTypeId' => 'required|exists:leave_types,id',
            'bulkCredits'     => 'required|numeric|min:0|max:365',
            'bulkMode'        => 'required|in:set,add',
        ]);

        $employees = Employee::where('is_active', true)->get();
        $count     = 0;

        foreach ($employees as $employee) {
            $credit = LeaveCredit::firstOrNew([
                'employee_id'   => $employee->id,
                'leave_type_id' => $this->bulkLeaveTypeId,
                'year'          => $this->year,
            ]);

            if ($this->bulkMode === 'add') {
                $credit->total_credits = ($credit->total_credits ?? 0) + (float) $this->bulkCredits;
            } else {
                $credit->total_credits = (float) $this->bulkCredits;
            }

            $credit->used_credits = $credit->used_credits ?? 0;
            $credit->save();
            $count++;
        }

        $type = LeaveType::find($this->bulkLeaveTypeId);
        $action = $this->bulkMode === 'add' ? "Added {$this->bulkCredits} to" : "Set {$this->bulkCredits} for";
        ActivityLogService::log('leave_credits_bulk', "{$action} {$type->name} credits for {$count} employees ({$this->year}).");

        $this->showBulkModal = false;
        unset($this->employees);
        session()->flash('success', "{$action} {$type->name} — {$count} employees updated.");
    }

    // ── Reset All ──────────────────────────────────────────

    public function resetCreditsForYear(): void
    {
        $leaveTypes = LeaveType::all();
        $employees  = Employee::where('is_active', true)->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                LeaveCredit::updateOrCreate(
                    [
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $type->id,
                        'year'          => $this->year,
                    ],
                    [
                        'total_credits' => $type->max_days_per_year,
                        'used_credits'  => 0,
                    ]
                );
            }
        }

        ActivityLogService::log('leave_credits_reset', "Reset all leave credits for {$this->year}.");
        unset($this->employees);
        session()->flash('success', "Credits reset for {$this->year}.");
    }

    public function render()
    {
        return view('livewire.admin.leave-credit-manager');
    }
}
