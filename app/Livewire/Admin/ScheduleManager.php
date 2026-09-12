<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduleManager extends Component
{
    use WithPagination;

    // Tab
    public string $tab = 'templates'; // templates | assignments

    // ------ Schedule Template filters / modal ------
    public string $filterDept  = '';
    public string $searchSched = '';

    public bool    $showSchedModal = false;
    public ?int    $editSchedId    = null;
    public string  $schedName      = '';
    public string  $schedDept      = '';
    public string  $schedTimeIn    = '';
    public string  $schedTimeOut   = '';
    public string  $schedBreakStart = '';
    public string  $schedBreakEnd   = '';
    public string  $schedTimeIn2    = '';
    public string  $schedTimeOut2   = '';
    public bool    $schedNightShift = false;
    public string  $schedDescription = '';

    // ------ Assignment filters / modal ------
    public string $assignFilterDept   = '';
    public string $assignSearch       = '';

    public bool    $showAssignModal   = false;
    public string  $assignEmpSearch   = '';
    public ?int    $assignEmployeeId  = null;   // single employee — used only when editing an existing assignment
    public array   $assignEmployeeIds = [];     // multiple employees — used when creating new assignments
    public string  $assignEmpDeptFilter = '';   // narrows the employee picker / powers "add all in department"
    public ?int    $assignScheduleId  = null;
    public string  $assignFrom        = '';
    public string  $assignTo          = '';
    public ?int    $editAssignId      = null;

    // ------ Bulk Assign modal ------
    public bool   $showBulkModal     = false;
    public string $bulkDept          = '';
    public ?int   $bulkScheduleId    = null;
    public string $bulkFrom          = '';
    public string $bulkTo            = '';

    public function updatedTab(): void { $this->resetPage(); }
    public function updatedFilterDept(): void { $this->resetPage(); }
    public function updatedSearchSched(): void { $this->resetPage(); }
    public function updatedAssignFilterDept(): void { $this->resetPage(); }
    public function updatedAssignSearch(): void { $this->resetPage(); }

    // ==================== COMPUTED ====================

    #[Computed]
    public function schedules()
    {
        return Schedule::query()
            ->when($this->filterDept, fn ($q) => $q->where('department', $this->filterDept))
            ->when($this->searchSched, fn ($q) => $q->where('name', 'like', "%{$this->searchSched}%"))
            ->orderBy('department')
            ->orderBy('time_in')
            ->paginate(25);
    }

    #[Computed]
    public function assignments()
    {
        return EmployeeSchedule::with(['employee', 'schedule'])
            ->when($this->assignSearch, function ($q) {
                $q->whereHas('employee', function ($q2) {
                    $q2->where('first_name', 'like', "%{$this->assignSearch}%")
                       ->orWhere('last_name', 'like', "%{$this->assignSearch}%")
                       ->orWhere('emp_code', 'like', "%{$this->assignSearch}%");
                });
            })
            ->when($this->assignFilterDept, function ($q) {
                $q->whereHas('employee', fn ($q2) => $q2->where('department', $this->assignFilterDept));
            })
            ->orderByDesc('effective_from')
            ->paginate(25);
    }

    #[Computed]
    public function departments(): array
    {
        return Employee::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function scheduleDepartments(): array
    {
        return Schedule::where('is_active', true)
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function employeeResults()
    {
        if (strlen($this->assignEmpSearch) < 2) {
            return collect();
        }

        return Employee::where('is_active', true)
            ->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->assignEmpSearch}%")
                  ->orWhere('last_name', 'like', "%{$this->assignEmpSearch}%")
                  ->orWhere('emp_code', 'like', "%{$this->assignEmpSearch}%");
            })
            // Already-picked employees are shown in the selected list instead —
            // only relevant in multi-select (create) mode, not while editing.
            ->when(!$this->editAssignId && $this->assignEmployeeIds, fn ($q) => $q->whereNotIn('id', $this->assignEmployeeIds))
            ->limit(10)
            ->get();
    }

    /**
     * The employees currently picked for a new (multi-employee) assignment.
     */
    #[Computed]
    public function selectedAssignEmployees()
    {
        if (empty($this->assignEmployeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $this->assignEmployeeIds)->get();
    }

    #[Computed]
    public function allSchedules()
    {
        return Schedule::where('is_active', true)
            ->orderBy('department')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function deptSchedules()
    {
        $dept = trim($this->bulkDept);
        if (!$dept) return collect();

        // Department names are free-typed on both the Employee record and the
        // Schedule template form, so a case or whitespace difference (e.g.
        // "Accounting" vs "accounting ") would otherwise silently match nothing
        // here even though a template clearly exists for that department.
        return Schedule::where('is_active', true)
            ->whereRaw('LOWER(TRIM(department)) = ?', [mb_strtolower($dept)])
            ->orderBy('name')
            ->get();
    }

    // ==================== SCHEDULE TEMPLATE CRUD ====================

    public function openAddSchedule(): void
    {
        $this->reset([
            'editSchedId', 'schedName', 'schedDept', 'schedTimeIn', 'schedTimeOut',
            'schedBreakStart', 'schedBreakEnd', 'schedTimeIn2', 'schedTimeOut2',
            'schedNightShift', 'schedDescription',
        ]);
        $this->showSchedModal = true;
    }

    public function openEditSchedule(int $id): void
    {
        $sched = Schedule::findOrFail($id);
        $this->editSchedId     = $sched->id;
        $this->schedName       = $sched->name;
        $this->schedDept       = $sched->department;
        $this->schedTimeIn     = substr($sched->time_in, 0, 5);
        $this->schedTimeOut    = substr($sched->time_out, 0, 5);
        $this->schedBreakStart = $sched->break_start ? substr($sched->break_start, 0, 5) : '';
        $this->schedBreakEnd   = $sched->break_end ? substr($sched->break_end, 0, 5) : '';
        $this->schedTimeIn2    = $sched->time_in_2 ? substr($sched->time_in_2, 0, 5) : '';
        $this->schedTimeOut2   = $sched->time_out_2 ? substr($sched->time_out_2, 0, 5) : '';
        $this->schedNightShift = $sched->is_night_shift;
        $this->schedDescription = $sched->description ?? '';
        $this->showSchedModal  = true;
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'schedName'    => 'required|string|max:100',
            'schedDept'    => 'required|string|max:100',
            'schedTimeIn'  => 'required',
            'schedTimeOut' => 'required',
        ]);

        $data = [
            'name'           => $this->schedName,
            'department'     => $this->schedDept,
            'time_in'        => $this->schedTimeIn,
            'time_out'       => $this->schedTimeOut,
            'break_start'    => $this->schedBreakStart ?: null,
            'break_end'      => $this->schedBreakEnd ?: null,
            'time_in_2'      => $this->schedTimeIn2 ?: null,
            'time_out_2'     => $this->schedTimeOut2 ?: null,
            'is_night_shift' => $this->schedNightShift,
            'description'    => $this->schedDescription ?: null,
            'created_by'     => Auth::id(),
        ];

        if ($this->editSchedId) {
            Schedule::where('id', $this->editSchedId)->update($data);
            session()->flash('message', 'Schedule updated.');
        } else {
            Schedule::create($data);
            session()->flash('message', 'Schedule created.');
        }

        $this->showSchedModal = false;
        unset($this->schedules);
    }

    public function toggleScheduleActive(int $id): void
    {
        $sched = Schedule::findOrFail($id);
        $sched->update(['is_active' => !$sched->is_active]);
        unset($this->schedules);
    }

    public function deleteSchedule(int $id): void
    {
        // schedules -> employee_schedules is cascadeOnDelete at the DB level,
        // so deleting a template still in use would silently wipe out every
        // employee's assignment to it. Block that and point the admin at
        // deactivating instead, which keeps assignments intact.
        // Only count currently-active assignments — an expired assignment
        // (effective_to in the past) shouldn't permanently block retiring a
        // template no one is actually on anymore.
        $today = now()->toDateString();
        $inUseCount = EmployeeSchedule::where('schedule_id', $id)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today);
            })
            ->count();

        if ($inUseCount > 0) {
            session()->flash('error', "Can't delete — this schedule is assigned to {$inUseCount} employee(s). Reassign or remove those assignments first, or deactivate the schedule instead.");
            return;
        }

        Schedule::where('id', $id)->delete();
        session()->flash('message', 'Schedule deleted.');
        unset($this->schedules);
    }

    // ==================== ASSIGNMENT CRUD ====================

    public function openAssign(): void
    {
        $this->reset([
            'editAssignId', 'assignEmpSearch', 'assignEmployeeId', 'assignEmployeeIds',
            'assignEmpDeptFilter', 'assignScheduleId', 'assignFrom', 'assignTo',
        ]);
        $this->assignFrom = now()->format('Y-m-d');
        $this->showAssignModal = true;
        unset($this->selectedAssignEmployees);
    }

    /**
     * Add an employee to the picker. While editing an existing assignment this
     * replaces the single employee; otherwise it adds to the multi-select list
     * used when creating new assignments for a group at once.
     */
    public function selectEmployee(int $id): void
    {
        if ($this->editAssignId) {
            $emp = Employee::find($id);
            if ($emp) {
                $this->assignEmployeeId = $emp->id;
                $this->assignEmpSearch  = $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->emp_code . ')';
            }
            return;
        }

        if (! in_array($id, $this->assignEmployeeIds, true)) {
            $this->assignEmployeeIds[] = $id;
            unset($this->selectedAssignEmployees, $this->employeeResults);
        }
        $this->assignEmpSearch = '';
    }

    public function removeAssignEmployee(int $id): void
    {
        $this->assignEmployeeIds = array_values(array_diff($this->assignEmployeeIds, [$id]));
        unset($this->selectedAssignEmployees, $this->employeeResults);
    }

    /**
     * Add every active employee in the currently filtered department to the
     * multi-select list in one click — e.g. "all Nursing staff".
     */
    public function selectAllInDept(): void
    {
        if (! $this->assignEmpDeptFilter) {
            return;
        }

        $ids = Employee::where('is_active', true)
            ->where('department', $this->assignEmpDeptFilter)
            ->pluck('id')
            ->all();

        $this->assignEmployeeIds = array_values(array_unique(array_merge($this->assignEmployeeIds, $ids)));
        unset($this->selectedAssignEmployees, $this->employeeResults);
    }

    public function openEditAssign(int $id): void
    {
        $assign = EmployeeSchedule::with('employee')->findOrFail($id);
        $this->editAssignId     = $assign->id;
        $this->assignEmployeeId = $assign->employee_id;
        $this->assignEmployeeIds = [];
        $this->assignScheduleId = $assign->schedule_id;
        $this->assignFrom       = $assign->effective_from->format('Y-m-d');
        $this->assignTo         = $assign->effective_to ? $assign->effective_to->format('Y-m-d') : '';
        $this->assignEmpSearch  = $assign->employee->first_name . ' ' . $assign->employee->last_name;
        $this->showAssignModal  = true;
    }

    public function saveAssign(): void
    {
        if ($this->editAssignId) {
            $this->validate([
                'assignEmployeeId' => 'required|exists:employees,id',
                'assignScheduleId' => 'required|exists:schedules,id',
                'assignFrom'       => 'required|date',
            ]);

            EmployeeSchedule::where('id', $this->editAssignId)->update([
                'employee_id'    => $this->assignEmployeeId,
                'schedule_id'    => $this->assignScheduleId,
                'effective_from' => $this->assignFrom,
                'effective_to'   => $this->assignTo ?: null,
                'created_by'     => Auth::id(),
            ]);
            session()->flash('message', 'Assignment updated.');
        } else {
            $this->validate([
                'assignEmployeeIds'   => 'required|array|min:1',
                'assignEmployeeIds.*' => 'exists:employees,id',
                'assignScheduleId'    => 'required|exists:schedules,id',
                'assignFrom'          => 'required|date',
            ]);

            $count = $this->createAssignments(
                $this->assignEmployeeIds,
                $this->assignScheduleId,
                $this->assignFrom,
                $this->assignTo ?: null,
            );

            session()->flash('message', "Schedule assigned to {$count} employee(s).");
        }

        $this->showAssignModal = false;
        unset($this->assignments);
    }

    public function deleteAssign(int $id): void
    {
        EmployeeSchedule::where('id', $id)->delete();
        session()->flash('message', 'Assignment removed.');
        unset($this->assignments);
    }

    // ==================== BULK ASSIGN ====================

    public function openBulk(): void
    {
        $this->reset(['bulkDept', 'bulkScheduleId', 'bulkFrom', 'bulkTo']);
        $this->bulkFrom = now()->format('Y-m-d');
        $this->showBulkModal = true;
    }

    public function bulkAssign(): void
    {
        $this->validate([
            'bulkDept'       => 'required|string',
            'bulkScheduleId' => 'required|exists:schedules,id',
            'bulkFrom'       => 'required|date',
        ]);

        $employeeIds = Employee::where('department', $this->bulkDept)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $count = $this->createAssignments(
            $employeeIds,
            $this->bulkScheduleId,
            $this->bulkFrom,
            $this->bulkTo ?: null,
        );

        $this->showBulkModal = false;
        session()->flash('message', "Schedule assigned to {$count} employees in {$this->bulkDept}.");
        unset($this->assignments);
    }

    /**
     * Shared by the "assign to multiple employees" and Bulk Assign flows —
     * both boil down to "create an EmployeeSchedule row per employee ID for
     * the same schedule/date range." Wrapped in a transaction so a failure
     * partway through a large batch doesn't leave some employees assigned
     * and others not.
     */
    private function createAssignments(array $employeeIds, int $scheduleId, string $from, ?string $to): int
    {
        return DB::transaction(function () use ($employeeIds, $scheduleId, $from, $to) {
            foreach ($employeeIds as $employeeId) {
                EmployeeSchedule::create([
                    'employee_id'    => $employeeId,
                    'schedule_id'    => $scheduleId,
                    'effective_from' => $from,
                    'effective_to'   => $to,
                    'created_by'     => Auth::id(),
                ]);
            }

            return count($employeeIds);
        });
    }

    // ==================== RENDER ====================

    public function render()
    {
        return view('livewire.admin.schedule-manager');
    }
}
