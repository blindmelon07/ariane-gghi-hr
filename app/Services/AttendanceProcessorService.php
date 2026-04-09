<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class AttendanceProcessorService
{
    /**
     * Process attendance for a given employee on a specific date.
     *
     * @return array{time_in: ?string, time_out: ?string, hours_worked: float, minutes_late: int, minutes_undertime: int, status: string}
     */
    public function processDay(Employee $employee, string $date): array
    {
        $carbon = Carbon::parse($date);

        // Sunday is a day-off
        if ($carbon->isSunday()) {
            return [
                'time_in'          => null,
                'time_out'         => null,
                'hours_worked'     => 0,
                'minutes_late'     => 0,
                'minutes_undertime' => 0,
                'status'           => 'Day-off',
            ];
        }

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_date', $date)
            ->orderBy('punch_time')
            ->get();

        // No records at all → Absent
        if ($logs->isEmpty()) {
            return [
                'time_in'          => null,
                'time_out'         => null,
                'hours_worked'     => 0,
                'minutes_late'     => 0,
                'minutes_undertime' => 0,
                'status'           => 'Absent',
            ];
        }

        $timeIn  = $logs->first()->punch_time;
        $timeOut = $logs->count() > 1 ? $logs->last()->punch_time : null;

        // Schedule constants
        $scheduleIn  = Carbon::parse($date)->setTime(8, 0, 0);
        $scheduleOut = Carbon::parse($date)->setTime(17, 0, 0);

        $minutesLate     = (int) max(0, $timeIn->diffInMinutes($scheduleIn, false) * -1);
        $minutesUndertime = 0;
        $hoursWorked     = 0;

        if ($timeOut) {
            $minutesUndertime = (int) max(0, $scheduleOut->diffInMinutes($timeOut, false) * -1);
            $hoursWorked      = round($timeOut->floatDiffInHours($timeIn), 2);
        }

        // Determine status
        $status = 'Absent';

        if ($timeIn && $timeOut) {
            if ($hoursWorked < 4) {
                $status = 'Half-day';
            } elseif ($minutesLate > 0) {
                $status = 'Late';
            } else {
                $status = 'Present';
            }
        } elseif ($timeIn) {
            // Has time_in but no time_out
            $status = 'Half-day';
        }

        return [
            'time_in'           => $timeIn->format('h:i A'),
            'time_out'          => $timeOut?->format('h:i A'),
            'hours_worked'      => $hoursWorked,
            'minutes_late'      => $minutesLate,
            'minutes_undertime' => $minutesUndertime,
            'status'            => $status,
        ];
    }
}
