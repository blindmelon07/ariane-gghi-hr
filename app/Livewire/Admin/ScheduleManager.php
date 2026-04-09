<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
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
    public ?int    $assignEmployeeId  = null;
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
            ->limit(10)
            ->get();
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
        $dept = $this->bulkDept;
        if (!$dept) return collect();

        return Schedule::where('is_active', true)
            ->where('department', $dept)
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
        Schedule::where('id', $id)->delete();
        session()->flash('message', 'Schedule deleted.');
        unset($this->schedules);
    }

    // ==================== ASSIGNMENT CRUD ====================

    public function openAssign(): void
    {
        $this->reset([
            'editAssignId', 'assignEmpSearch', 'assignEmployeeId',
            'assignScheduleId', 'assignFrom', 'assignTo',
        ]);
        $this->assignFrom = now()->format('Y-m-d');
        $this->showAssignModal = true;
    }

    public function selectEmployee(int $id): void
    {
        $emp = Employee::find($id);
        if ($emp) {
            $this->assignEmployeeId = $emp->id;
            $this->assignEmpSearch  = $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->emp_code . ')';
        }
    }

    public function openEditAssign(int $id): void
    {
        $assign = EmployeeSchedule::with('employee')->findOrFail($id);
        $this->editAssignId     = $assign->id;
        $this->assignEmployeeId = $assign->employee_id;
        $this->assignScheduleId = $assign->schedule_id;
        $this->assignFrom       = $assign->effective_from->format('Y-m-d');
        $this->assignTo         = $assign->effective_to ? $assign->effective_to->format('Y-m-d') : '';
        $this->assignEmpSearch  = $assign->employee->first_name . ' ' . $assign->employee->last_name;
        $this->showAssignModal  = true;
    }

    public function saveAssign(): void
    {
        $this->validate([
            'assignEmployeeId' => 'required|exists:employees,id',
            'assignScheduleId' => 'required|exists:schedules,id',
            'assignFrom'       => 'required|date',
        ]);

        $data = [
            'employee_id'    => $this->assignEmployeeId,
            'schedule_id'    => $this->assignScheduleId,
            'effective_from' => $this->assignFrom,
            'effective_to'   => $this->assignTo ?: null,
            'created_by'     => Auth::id(),
        ];

        if ($this->editAssignId) {
            EmployeeSchedule::where('id', $this->editAssignId)->update($data);
            session()->flash('message', 'Assignment updated.');
        } else {
            EmployeeSchedule::create($data);
            session()->flash('message', 'Schedule assigned to employee.');
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

        $employees = Employee::where('department', $this->bulkDept)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($employees as $emp) {
            EmployeeSchedule::create([
                'employee_id'    => $emp->id,
                'schedule_id'    => $this->bulkScheduleId,
                'effective_from' => $this->bulkFrom,
                'effective_to'   => $this->bulkTo ?: null,
                'created_by'     => Auth::id(),
            ]);
            $count++;
        }

        $this->showBulkModal = false;
        session()->flash('message', "Schedule assigned to {$count} employees in {$this->bulkDept}.");
        unset($this->assignments);
    }

    // ==================== RENDER ====================

    public function render()
    {
        return view('livewire.admin.schedule-manager');
    }
}
