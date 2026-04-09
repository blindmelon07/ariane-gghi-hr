<?php

namespace App\Livewire\Admin;

use App\Models\DayOff;
use App\Models\Employee;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DayOffManager extends Component
{
    use WithPagination;

    // Filters
    public string $search       = '';
    public string $filterDept   = '';
    public string $filterType   = '';
    public string $filterMonth  = '';

    // Single day-off modal
    public bool    $showModal       = false;
    public ?int    $editId          = null;
    public string  $empSearch       = '';
    public ?int    $modalEmployeeId = null;
    public string  $date            = '';
    public string  $type            = 'rest_day';
    public string  $description     = '';
    public string  $mode            = 'single'; // single | recurring
    public array   $selectedDays    = [];       // [0,1,2..6] for recurring
    public string  $dateFrom        = '';
    public string  $dateTo          = '';

    // Bulk assign modal
    public bool   $showBulkModal    = false;
    public string $bulkDept         = '';
    public string $bulkType         = 'rest_day';
    public string $bulkDescription  = '';
    public string $bulkDateFrom     = '';
    public string $bulkDateTo       = '';
    public array  $bulkSelectedDays = [];

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterDept(): void { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }
    public function updatedFilterMonth(): void { $this->resetPage(); }

    #[Computed]
    public function dayOffs()
    {
        $query = DayOff::with('employee')
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($q2) {
                    $q2->where('first_name', 'like', "%{$this->search}%")
                       ->orWhere('last_name', 'like', "%{$this->search}%")
                       ->orWhere('emp_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterDept, function ($q) {
                $q->whereHas('employee', fn ($q2) => $q2->where('department', $this->filterDept));
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterMonth, function ($q) {
                $start = Carbon::parse($this->filterMonth . '-01')->startOfMonth();
                $end   = $start->copy()->endOfMonth();
                $q->whereBetween('date', [$start, $end]);
            })
            ->orderByDesc('date')
            ->paginate(25);

        return $query;
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
    public function employeeResults()
    {
        if (strlen($this->empSearch) < 2) {
            return collect();
        }

        return Employee::where('is_active', true)
            ->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->empSearch}%")
                  ->orWhere('last_name', 'like', "%{$this->empSearch}%")
                  ->orWhere('emp_code', 'like', "%{$this->empSearch}%");
            })
            ->limit(10)
            ->get();
    }

    // ── Single Day Off ───────────────────────────────

    public function openAdd(): void
    {
        $this->reset(['editId', 'empSearch', 'modalEmployeeId', 'date', 'type', 'description', 'mode', 'selectedDays', 'dateFrom', 'dateTo']);
        $this->type = 'rest_day';
        $this->mode = 'single';
        $this->showModal = true;
    }

    public function selectEmployee(int $id): void
    {
        $emp = Employee::find($id);
        if ($emp) {
            $this->modalEmployeeId = $emp->id;
            $this->empSearch = $emp->full_name . ' (' . $emp->emp_code . ')';
        }
    }

    public function openEdit(int $id): void
    {
        $dayOff = DayOff::with('employee')->findOrFail($id);

        $this->editId          = $dayOff->id;
        $this->modalEmployeeId = $dayOff->employee_id;
        $this->empSearch       = $dayOff->employee->full_name . ' (' . $dayOff->employee->emp_code . ')';
        $this->date            = $dayOff->date->format('Y-m-d');
        $this->type            = $dayOff->type;
        $this->description     = $dayOff->description ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editId) {
            // Edit mode — single date only
            $this->validate([
                'modalEmployeeId' => 'required|exists:employees,id',
                'date'            => 'required|date',
                'type'            => 'required|in:rest_day,holiday,special,other',
                'description'     => 'nullable|string|max:255',
            ]);

            $dayOff = DayOff::findOrFail($this->editId);
            $dayOff->update([
                'employee_id' => $this->modalEmployeeId,
                'date'        => $this->date,
                'type'        => $this->type,
                'description' => $this->description ?: null,
                'created_by'  => auth()->id(),
            ]);

            $emp = Employee::find($this->modalEmployeeId);
            ActivityLogService::log('day_off_updated', "Day off updated for {$emp->full_name} on {$this->date}", $emp);
            $this->showModal = false;
            unset($this->dayOffs);
            session()->flash('success', 'Day off updated.');
            return;
        }

        // Create mode
        $this->validate([
            'modalEmployeeId' => 'required|exists:employees,id',
            'type'            => 'required|in:rest_day,holiday,special,other',
            'description'     => 'nullable|string|max:255',
        ]);

        $emp = Employee::find($this->modalEmployeeId);

        if ($this->mode === 'recurring') {
            $this->validate([
                'selectedDays' => 'required|array|min:1',
                'dateFrom'     => 'required|date',
                'dateTo'       => 'required|date|after_or_equal:dateFrom',
            ]);

            $start = Carbon::parse($this->dateFrom);
            $end   = Carbon::parse($this->dateTo);
            $count = 0;
            $days  = array_map('intval', $this->selectedDays);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if (!in_array($d->dayOfWeek, $days)) {
                    continue;
                }

                $exists = DayOff::where('employee_id', $this->modalEmployeeId)
                    ->where('date', $d->format('Y-m-d'))
                    ->exists();

                if (!$exists) {
                    DayOff::create([
                        'employee_id'           => $this->modalEmployeeId,
                        'date'                  => $d->format('Y-m-d'),
                        'type'                  => $this->type,
                        'description'           => $this->description ?: null,
                        'is_recurring'          => true,
                        'recurring_day_of_week' => $d->dayOfWeek,
                        'created_by'            => auth()->id(),
                    ]);
                    $count++;
                }
            }

            ActivityLogService::log('recurring_day_off', "Assigned {$count} recurring day offs to {$emp->full_name}", $emp);
            $this->showModal = false;
            unset($this->dayOffs);
            session()->flash('success', "{$count} day off(s) created for {$emp->full_name}.");
        } else {
            // Single date
            $this->validate([
                'date' => 'required|date',
            ]);

            $exists = DayOff::where('employee_id', $this->modalEmployeeId)
                ->where('date', $this->date)
                ->exists();

            if ($exists) {
                $this->addError('date', 'This employee already has a day off on this date.');
                return;
            }

            DayOff::create([
                'employee_id' => $this->modalEmployeeId,
                'date'        => $this->date,
                'type'        => $this->type,
                'description' => $this->description ?: null,
                'created_by'  => auth()->id(),
            ]);

            ActivityLogService::log('day_off_assigned', "Day off ({$this->type}) assigned to {$emp->full_name} on {$this->date}", $emp);
            $this->showModal = false;
            unset($this->dayOffs);
            session()->flash('success', 'Day off saved successfully.');
        }
    }

    public function delete(int $id): void
    {
        $dayOff = DayOff::with('employee')->findOrFail($id);
        $name = $dayOff->employee->full_name;
        $date = $dayOff->date->format('M d, Y');
        $dayOff->delete();

        ActivityLogService::log('day_off_removed', "Day off removed for {$name} on {$date}");
        unset($this->dayOffs);
        session()->flash('success', "Day off removed for {$name}.");
    }

    // ── Bulk Assign ──────────────────────────────────

    public function openBulk(): void
    {
        $this->reset(['bulkDept', 'bulkType', 'bulkDescription', 'bulkDateFrom', 'bulkDateTo', 'bulkSelectedDays']);
        $this->bulkType = 'rest_day';
        $this->showBulkModal = true;
    }

    public function bulkAssign(): void
    {
        $this->validate([
            'bulkDept'         => 'required|string',
            'bulkType'         => 'required|in:rest_day,holiday,special,other',
            'bulkSelectedDays' => 'required|array|min:1',
            'bulkDateFrom'     => 'required|date',
            'bulkDateTo'       => 'required|date|after_or_equal:bulkDateFrom',
        ]);

        $employees = Employee::where('is_active', true)
            ->where('department', $this->bulkDept)
            ->get();

        $start = Carbon::parse($this->bulkDateFrom);
        $end   = Carbon::parse($this->bulkDateTo);
        $days  = array_map('intval', $this->bulkSelectedDays);
        $count = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!in_array($d->dayOfWeek, $days)) {
                continue;
            }

            foreach ($employees as $emp) {
                $exists = DayOff::where('employee_id', $emp->id)
                    ->where('date', $d->format('Y-m-d'))
                    ->exists();

                if (!$exists) {
                    DayOff::create([
                        'employee_id'           => $emp->id,
                        'date'                  => $d->format('Y-m-d'),
                        'type'                  => $this->bulkType,
                        'description'           => $this->bulkDescription ?: null,
                        'is_recurring'          => true,
                        'recurring_day_of_week' => $d->dayOfWeek,
                        'created_by'            => auth()->id(),
                    ]);
                    $count++;
                }
            }
        }

        ActivityLogService::log('bulk_day_off', "Bulk assigned {$count} day offs for dept {$this->bulkDept}");

        $this->showBulkModal = false;
        unset($this->dayOffs);
        session()->flash('success', "{$count} day off(s) assigned to {$employees->count()} employees in {$this->bulkDept}.");
    }

    public function render()
    {
        return view('livewire.admin.day-off-manager');
    }
}
