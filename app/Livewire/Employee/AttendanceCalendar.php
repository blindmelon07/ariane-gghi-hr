<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\UndertimeRequest;
use App\Services\AttendanceProcessorService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AttendanceCalendar extends Component
{
    public int $month;
    public int $year;

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
        unset($this->attendanceData);
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year  = $date->year;
        unset($this->attendanceData);
    }

    #[Computed]
    public function attendanceData(): array
    {
        $employee = Employee::where('emp_code', auth()->user()->employee_code)->first();

        if (!$employee) {
            return [];
        }

        $processor = app(AttendanceProcessorService::class);
        $start     = Carbon::create($this->year, $this->month, 1);
        $end       = $start->copy()->endOfMonth();
        $data      = [];

        // Undertime the employee had approved still shows, but as excused.
        $excusedUndertime = UndertimeRequest::approved()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [$d->toDateString() => true]);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $dateStr = $day->toDateString();
            $result  = $processor->processDayForDisplay($employee, $dateStr);

            $data[$dateStr] = array_merge($result, [
                'day'       => $day->day,
                'dayOfWeek' => $day->dayOfWeek,
                'undertime_approved' => isset($excusedUndertime[$dateStr]),
            ]);
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.employee.attendance-calendar');
    }
}
