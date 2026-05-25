<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class AttendanceProcessorService
{
    // Punch times before this boundary are treated as AM; at/after are PM.
    private const AM_PM_BOUNDARY = '12:30:00';

    /**
     * @return array{
     *   am_time_in: ?string, am_time_out: ?string,
     *   pm_time_in: ?string, pm_time_out: ?string,
     *   time_in: ?string, time_out: ?string,
     *   hours_worked: float, minutes_late: int, minutes_undertime: int, status: string
     * }
     */
    public function processDay(Employee $employee, string $date): array
    {
        $hasDayOff = DayOff::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->exists();

        if ($hasDayOff) {
            return $this->emptyResult('Day-off');
        }

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_date', $date)
            ->orderBy('punch_time')
            ->get();

        if ($logs->isEmpty()) {
            return $this->emptyResult('Absent');
        }

        $boundary = Carbon::parse($date . ' ' . self::AM_PM_BOUNDARY);

        $amLogs = $logs->filter(fn ($l) => $l->punch_time->lt($boundary))->values();
        $pmLogs = $logs->filter(fn ($l) => $l->punch_time->gte($boundary))->values();

        $amIn  = $amLogs->first()?->punch_time;
        $amOut = $amLogs->count() > 1 ? $amLogs->last()->punch_time : null;
        $pmIn  = $pmLogs->first()?->punch_time;
        $pmOut = $pmLogs->count() > 1 ? $pmLogs->last()->punch_time : null;

        // Lookup schedule (falls back to 08:00–17:00)
        $scheduleIn  = Carbon::parse($date)->setTime(8, 0, 0);
        $scheduleOut = Carbon::parse($date)->setTime(17, 0, 0);

        $assignment = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->with('schedule')
            ->first();

        if ($assignment?->schedule) {
            $inParts  = explode(':', substr($assignment->schedule->time_in, 0, 5));
            $outParts = explode(':', substr($assignment->schedule->time_out, 0, 5));
            $scheduleIn  = Carbon::parse($date)->setTime((int) $inParts[0], (int) $inParts[1], 0);
            $scheduleOut = Carbon::parse($date)->setTime((int) $outParts[0], (int) $outParts[1], 0);
        }

        $pmScheduleIn = Carbon::parse($date)->setTime(13, 0, 0);

        // Late = AM late + PM late
        $amLate = $amIn ? (int) max(0, $amIn->diffInMinutes($scheduleIn, false) * -1) : 0;
        $pmLate = $pmIn ? (int) max(0, $pmIn->diffInMinutes($pmScheduleIn, false) * -1) : 0;
        $minutesLate = $amLate + $pmLate;

        $amHours = ($amIn && $amOut)
            ? round(abs($amOut->getTimestamp() - $amIn->getTimestamp()) / 3600, 2)
            : 0;
        $pmHours = ($pmIn && $pmOut)
            ? round(abs($pmOut->getTimestamp() - $pmIn->getTimestamp()) / 3600, 2)
            : 0;
        $hoursWorked = round($amHours + $pmHours, 2);

        $minutesUndertime = $pmOut
            ? (int) max(0, $scheduleOut->diffInMinutes($pmOut, false) * -1)
            : 0;

        // Status
        $timeIn  = $amIn ?? $pmIn;
        $timeOut = $pmOut ?? $amOut;

        $status = match (true) {
            !$timeIn                    => 'Absent',
            $hoursWorked >= 4 && $minutesLate > 0 => 'Late',
            $hoursWorked >= 4           => 'Present',
            $hoursWorked > 0            => 'Half-day',
            default                     => 'Absent',
        };

        return [
            'am_time_in'        => $amIn?->format('h:i A'),
            'am_time_out'       => $amOut?->format('h:i A'),
            'pm_time_in'        => $pmIn?->format('h:i A'),
            'pm_time_out'       => $pmOut?->format('h:i A'),
            'time_in'           => $timeIn?->format('h:i A'),
            'time_out'          => $timeOut?->format('h:i A'),
            'hours_worked'      => $hoursWorked,
            'minutes_late'      => $minutesLate,
            'minutes_undertime' => $minutesUndertime,
            'status'            => $status,
        ];
    }

    private function emptyResult(string $status): array
    {
        return [
            'am_time_in'        => null,
            'am_time_out'       => null,
            'pm_time_in'        => null,
            'pm_time_out'       => null,
            'time_in'           => null,
            'time_out'          => null,
            'hours_worked'      => 0,
            'minutes_late'      => 0,
            'minutes_undertime' => 0,
            'status'            => $status,
        ];
    }
}
