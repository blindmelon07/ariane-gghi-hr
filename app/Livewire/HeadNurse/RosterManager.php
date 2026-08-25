<?php

namespace App\Livewire\HeadNurse;

use App\Models\Employee;
use App\Models\NurseDutyRoster;
use App\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RosterManager extends Component
{
    public int $month;
    public int $year;

    // Cell-edit modal
    public ?int $selectedEmployeeId = null;
    public ?string $selectedDate    = null;
    public ?int $selectedScheduleId = null;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year  = now()->year;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year  = $date->year;
        unset($this->rosterMap);
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year  = $date->year;
        unset($this->rosterMap);
    }

    #[Computed]
    public function nurses()
    {
        return Employee::where('department', 'Nursing')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function shiftTemplates()
    {
        return Schedule::where('department', 'Nursing')
            ->where('is_active', true)
            ->orderBy('time_in')
            ->get();
    }

    #[Computed]
    public function dates(): array
    {
        $start = Carbon::create($this->year, $this->month, 1);
        $end   = $start->copy()->endOfMonth();

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->copy();
        }

        return $dates;
    }

    /**
     * Keyed by "{employee_id}_{Y-m-d}" for O(1) cell lookups in the view.
     */
    #[Computed]
    public function rosterMap(): array
    {
        $start = Carbon::create($this->year, $this->month, 1)->toDateString();
        $end   = Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();

        return NurseDutyRoster::with('schedule')
            ->whereIn('employee_id', $this->nurses->pluck('id'))
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($entry) => $entry->employee_id . '_' . $entry->date->format('Y-m-d'))
            ->toArray();
    }

    public function openCell(int $employeeId, string $date): void
    {
        $this->selectedEmployeeId = $employeeId;
        $this->selectedDate       = $date;

        $existing = NurseDutyRoster::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        $this->selectedScheduleId = $existing?->schedule_id;

        $this->dispatch('open-roster-modal');
    }

    /**
     * Assign a shift (or mark explicitly OFF when scheduleId is null) for the
     * selected employee/date. Always leaves a row behind, distinguishing
     * "explicitly off" from "not yet scheduled" (no row at all).
     */
    public function saveCell(): void
    {
        if (!$this->selectedEmployeeId || !$this->selectedDate) {
            return;
        }

        // A plain array-keyed updateOrCreate() would compare 'date' with exact
        // equality, which only happens to work on MySQL's native DATE column
        // (it truncates any time component); under SQLite the cast attribute
        // is stored with a time part, so the lookup must go through whereDate().
        $existing = NurseDutyRoster::where('employee_id', $this->selectedEmployeeId)
            ->whereDate('date', $this->selectedDate)
            ->first();

        if ($existing) {
            $existing->update(['schedule_id' => $this->selectedScheduleId, 'created_by' => Auth::id()]);
        } else {
            NurseDutyRoster::create([
                'employee_id' => $this->selectedEmployeeId,
                'date'        => $this->selectedDate,
                'schedule_id' => $this->selectedScheduleId,
                'created_by'  => Auth::id(),
            ]);
        }

        $this->closeModal();
        unset($this->rosterMap);
    }

    /**
     * Remove the roster entry entirely, reverting the cell to "not scheduled".
     */
    public function clearCell(): void
    {
        if (!$this->selectedEmployeeId || !$this->selectedDate) {
            return;
        }

        NurseDutyRoster::where('employee_id', $this->selectedEmployeeId)
            ->whereDate('date', $this->selectedDate)
            ->delete();

        $this->closeModal();
        unset($this->rosterMap);
    }

    public function closeModal(): void
    {
        $this->selectedEmployeeId = null;
        $this->selectedDate       = null;
        $this->selectedScheduleId = null;
    }

    public function render()
    {
        return view('livewire.head-nurse.roster-manager');
    }
}
