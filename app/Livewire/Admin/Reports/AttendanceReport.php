<?php

namespace App\Livewire\Admin\Reports;

use App\Exports\AttendanceReportExport;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Services\AttendanceProcessorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReport extends Component
{
    use WithPagination;

    public string $dateFrom    = '';
    public string $dateTo      = '';
    public string $department  = '';
    public ?int   $employeeId  = null;
    public string $empSearch   = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }
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
                   ->orWhere('last_name', 'like', "%{$this->empSearch}%")
                   ->orWhere('emp_code', 'like', "%{$this->empSearch}%");
            }))
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'emp_code', 'first_name', 'last_name']);
    }

    #[Computed]
    public function reportData()
    {
        $processor = app(AttendanceProcessorService::class);

        $employees = Employee::where('is_active', true)
            ->when($this->department, fn ($q) => $q->where('department', $this->department))
            ->when($this->employeeId, fn ($q) => $q->where('id', $this->employeeId))
            ->orderBy('last_name')
            ->get();

        $rows = collect();

        foreach ($employees as $emp) {
            $start = \Carbon\Carbon::parse($this->dateFrom);
            $end   = \Carbon\Carbon::parse($this->dateTo);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->isSunday()) {
                    continue;
                }

                $day = $processor->processDay($emp, $d->toDateString());

                $rows->push([
                    'emp_code'   => $emp->emp_code,
                    'name'       => $emp->full_name,
                    'department' => $emp->department,
                    'date'       => $d->format('M d, Y'),
                    'date_raw'   => $d->toDateString(),
                    'time_in'    => $day['time_in'],
                    'time_out'   => $day['time_out'],
                    'hours'      => $day['hours_worked'],
                    'late_min'   => $day['minutes_late'],
                    'status'     => $day['status'],
                ]);
            }
        }

        return $rows;
    }

    #[Computed]
    public function paginatedReport()
    {
        $page    = $this->getPage();
        $perPage = 50;
        $data    = $this->reportData;
        $slice   = $data->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice, $data->count(), $perPage, $page,
            ['path' => request()->url()]
        );
    }

    public function exportExcel()
    {
        return Excel::download(
            new AttendanceReportExport($this->reportData),
            'attendance-report-' . $this->dateFrom . '-to-' . $this->dateTo . '.xlsx'
        );
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('pdf.attendance-report', [
            'rows'     => $this->reportData,
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
        ])->setPaper('A4', 'landscape');

        return response()->streamDownload(fn () => print($pdf->output()),
            'attendance-report-' . $this->dateFrom . '.pdf'
        );
    }

    public function selectEmployee(int $id): void
    {
        $this->employeeId = $id;
        $this->empSearch   = '';
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
    }

    public function render()
    {
        return view('livewire.admin.reports.attendance-report');
    }
}
