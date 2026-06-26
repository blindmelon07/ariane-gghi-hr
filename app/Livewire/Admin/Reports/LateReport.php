<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Employee;
use App\Services\AttendanceProcessorService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LateReport extends Component
{
    use WithPagination;

    public string $dateFrom   = '';
    public string $dateTo     = '';
    public string $department = '';
    public ?int   $employeeId = null;
    public string $empSearch  = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function updatedDateFrom(): void   { $this->resetPage(); }
    public function updatedDateTo(): void     { $this->resetPage(); }
    public function updatedDepartment(): void { $this->resetPage(); }
    public function updatedEmployeeId(): void { $this->resetPage(); }

    #[Computed]
    public function departments(): array
    {
        return Employee::whereNotNull('department')
            ->where('is_active', true)
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function employeeOptions()
    {
        return Employee::where('is_active', true)
            ->when($this->empSearch, fn ($q) => $q->where(function ($q2) {
                $q2->where('first_name', 'like', "%{$this->empSearch}%")
                   ->orWhere('last_name',  'like', "%{$this->empSearch}%")
                   ->orWhere('emp_code',   'like', "%{$this->empSearch}%");
            }))
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'emp_code', 'first_name', 'last_name']);
    }

    #[Computed]
    public function reportData(): \Illuminate\Support\Collection
    {
        $processor = app(AttendanceProcessorService::class);

        $employees = Employee::where('is_active', true)
            ->when($this->department, fn ($q) => $q->where('department', $this->department))
            ->when($this->employeeId, fn ($q) => $q->where('id', $this->employeeId))
            ->orderBy('last_name')
            ->get();

        $rows = collect();
        $start = Carbon::parse($this->dateFrom);
        $end   = Carbon::parse($this->dateTo);

        foreach ($employees as $emp) {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->isSunday()) {
                    continue;
                }

                $day = $processor->processDay($emp, $d->toDateString());

                if ($day['minutes_late'] <= 0) {
                    continue;
                }

                $rows->push([
                    'emp_code'   => $emp->emp_code,
                    'name'       => $emp->full_name,
                    'department' => $emp->department ?? '—',
                    'date'       => $d->format('M d, Y'),
                    'date_raw'   => $d->toDateString(),
                    'time_in'    => $day['am_time_in'] ?? $day['time_in'] ?? '—',
                    'late_min'   => $day['minutes_late'],
                    'status'     => $day['status'],
                ]);
            }
        }

        return $rows;
    }

    #[Computed]
    public function summaryCards(): array
    {
        $data = $this->reportData;

        return [
            'total_incidents'  => $data->count(),
            'total_late_min'   => $data->sum('late_min'),
            'unique_employees' => $data->pluck('emp_code')->unique()->count(),
            'worst_late_min'   => $data->max('late_min') ?? 0,
        ];
    }

    #[Computed]
    public function paginatedReport(): LengthAwarePaginator
    {
        $page    = $this->getPage();
        $perPage = 50;
        $data    = $this->reportData;
        $slice   = $data->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice, $data->count(), $perPage, $page,
            ['path' => request()->url()]
        );
    }

    public function selectEmployee(int $id): void
    {
        $this->employeeId = $id;
        $this->empSearch  = '';
        $this->resetPage();
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.reports.late-report');
    }
}
