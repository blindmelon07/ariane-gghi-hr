<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Services\AttendanceProcessorService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AttendanceSummary extends Component
{
    public int $month;
    public int $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year  = now()->year;
    }

    protected function getMonthData(): array
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            return [];
        }

        $processor = app(AttendanceProcessorService::class);
        $start     = Carbon::create($this->year, $this->month, 1);
        $end       = $start->copy()->endOfMonth();
        $data      = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $data[] = $processor->processDay($employee, $day->toDateString());
        }

        return $data;
    }

    #[Computed]
    public function totalPresent(): int
    {
        return collect($this->getMonthData())
            ->whereIn('status', ['Present', 'Late'])
            ->count();
    }

    #[Computed]
    public function totalAbsent(): int
    {
        return collect($this->getMonthData())
            ->where('status', 'Absent')
            ->count();
    }

    #[Computed]
    public function totalLate(): int
    {
        return collect($this->getMonthData())
            ->where('status', 'Late')
            ->count();
    }

    #[Computed]
    public function totalHours(): float
    {
        return round(
            collect($this->getMonthData())->sum('hours_worked'),
            2
        );
    }

    public function render()
    {
        return view('livewire.employee.attendance-summary');
    }
}
