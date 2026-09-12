<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\TimeCorrection;
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
        // 1. Company-wide holiday (highest priority)
        $holiday = Holiday::whereDate('date', $date)->first();

        // Also check recurring holidays (same month+day, any year)
        if (! $holiday) {
            $parsed = Carbon::parse($date);
            $holiday = Holiday::where('is_recurring', true)
                ->whereMonth('date', $parsed->month)
                ->whereDay('date', $parsed->day)
                ->first();
        }

        if ($holiday) {
            return $this->emptyResult($holiday->name);
        }

        // 2. Individual day-off
        $hasDayOff = DayOff::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->exists();

        if ($hasDayOff) {
            return $this->emptyResult('Day-off');
        }

        // 3. Approved leave
        $leave = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($leave) {
            return $this->emptyResult($leave->leaveType->name);
        }

        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_date', $date)
            ->orderBy('punch_time')
            ->get();

        // 4. Approved time correction — may supplement or replace biometric punches
        $correction = TimeCorrection::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('date', $date)
            ->latest()
            ->first();

        if ($logs->isEmpty() && ! $correction) {
            return $this->emptyResult('Absent');
        }

        $boundary = Carbon::parse($date . ' ' . self::AM_PM_BOUNDARY);

        $amLogs = $logs->filter(fn ($l) => $l->punch_time->lt($boundary))->values();
        $pmLogs = $logs->filter(fn ($l) => $l->punch_time->gte($boundary))->values();

        // Base times from biometric
        $amIn  = $amLogs->first()?->punch_time;
        $amOut = $amLogs->count() > 1 ? $amLogs->last()->punch_time : null;
        $pmIn  = $pmLogs->first()?->punch_time;
        $pmOut = $pmLogs->count() > 1 ? $pmLogs->last()->punch_time : null;

        // Override with approved correction where provided
        if ($correction) {
            if ($correction->am_time_in)  $amIn  = Carbon::parse($date . ' ' . $correction->am_time_in);
            if ($correction->am_time_out) $amOut = Carbon::parse($date . ' ' . $correction->am_time_out);
            if ($correction->pm_time_in)  $pmIn  = Carbon::parse($date . ' ' . $correction->pm_time_in);
            if ($correction->pm_time_out) $pmOut = Carbon::parse($date . ' ' . $correction->pm_time_out);
        }

        // Lookup schedule (falls back to 08:00–17:00, PM session at 13:00)
        $scheduleIn   = Carbon::parse($date)->setTime(8, 0, 0);
        $scheduleOut  = Carbon::parse($date)->setTime(17, 0, 0);
        $pmScheduleIn = Carbon::parse($date)->setTime(13, 0, 0);

        $assignment = \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->with('schedule')
            ->first();

        if ($assignment?->schedule) {
            $schedule = $assignment->schedule;

            $inParts  = explode(':', substr($schedule->time_in, 0, 5));
            $outParts = explode(':', substr($schedule->time_out, 0, 5));
            $scheduleIn  = Carbon::parse($date)->setTime((int) $inParts[0], (int) $inParts[1], 0);
            $scheduleOut = Carbon::parse($date)->setTime((int) $outParts[0], (int) $outParts[1], 0);

            // Split-shift templates set a distinct PM start (time_in_2) — use it
            // instead of the 13:00 default so lateness is measured against the
            // shift actually assigned, not a one-size-fits-all boundary.
            if ($schedule->time_in_2) {
                $in2Parts     = explode(':', substr($schedule->time_in_2, 0, 5));
                $pmScheduleIn = Carbon::parse($date)->setTime((int) $in2Parts[0], (int) $in2Parts[1], 0);
            }

            // TODO: is_night_shift is stored but not yet applied here. A shift whose
            // time_out crosses midnight (e.g. 22:00–06:00) needs its checkout matched
            // against the following calendar day's punches, which this day-by-day
            // punch_date grouping doesn't currently support. Needs a dedicated fix,
            // not a partial patch here.
        }

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

        // Incomplete: PM session started but employee never punched out at end of day,
        // OR only one punch recorded for the entire day (no time-out at all).
        $isIncomplete = ($pmIn !== null && $pmOut === null)
                     || ($timeIn !== null && $timeOut === null);

        $status = match (true) {
            !$timeIn                              => 'Absent',
            $isIncomplete                         => 'Incomplete',
            $hoursWorked >= 4 && $minutesLate > 0 => 'Late',
            $hoursWorked >= 4                     => 'Present',
            $hoursWorked > 0                      => 'Half-day',
            default                               => 'Absent',
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
            'hours_worked'      => 0.0,
            'minutes_late'      => 0,
            'minutes_undertime' => 0,
            'status'            => $status,
        ];
    }
}
