<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\DayOffSwap;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\TimeCorrection;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AttendanceProcessorService
{
    /**
     * Holiday lookups depend only on the date, not the employee, but reports call
     * processDay() once per employee per day. Memoising per instance turns two
     * queries per call into two queries per distinct date.
     *
     * @var array<string, ?Holiday>
     */
    private array $holidayCache = [];

    /**
     * Bulk-loaded employee/date data, populated by preload(). Reports that walk
     * every employee across a date range would otherwise issue five queries per
     * employee-day; preloading replaces that with five queries total. processDay()
     * only reads these when isPreloadedFor() confirms the employee and date are
     * inside the loaded window, so uncached callers keep their old behaviour.
     */
    private bool $preloaded = false;
    private array $preloadedEmployees = [];
    private string $preloadFrom = '';
    private string $preloadTo = '';
    private array $preDayOffs = [];
    private array $preLeaves = [];
    private array $preLogs = [];
    private array $preCorrections = [];
    private array $preSchedules = [];

    /**
     * Warm the per-employee/date caches for a whole range up front.
     *
     * @param  iterable<int>  $employeeIds
     */
    public function preload(iterable $employeeIds, string $from, string $to): void
    {
        $ids = collect($employeeIds)->map(fn ($id) => (int) $id)->unique()->values();

        $this->preloadedEmployees = $ids->mapWithKeys(fn ($id) => [$id => true])->all();
        $this->preloadFrom = $from;
        $this->preloadTo   = $to;
        $this->preDayOffs  = [];
        $this->preLeaves   = [];
        $this->preLogs     = [];
        $this->preCorrections = [];
        $this->preSchedules   = [];

        // Holidays are employee-independent, so warm them even when no employees were
        // passed: one query for the fixed dates in range, one for recurring entries.
        $this->warmHolidayCache($from, $to);

        if ($ids->isEmpty()) {
            $this->preloaded = true;

            return;
        }

        foreach (DayOff::whereIn('employee_id', $ids)->whereBetween('date', [$from, $to])->get(['employee_id', 'date']) as $off) {
            $this->preDayOffs[$this->key($off->employee_id, $off->date)] = true;
        }

        // Leave spans a range, so fan each approved request out over the days it covers.
        $leaves = LeaveRequest::with('leaveType')
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $windowStart = Carbon::parse($from);
        $windowEnd   = Carbon::parse($to);

        foreach ($leaves as $leave) {
            $cursor = Carbon::parse($leave->start_date)->max($windowStart);
            $last   = Carbon::parse($leave->end_date)->min($windowEnd);

            for ($d = $cursor->copy(); $d->lte($last); $d->addDay()) {
                $k = $this->key($leave->employee_id, $d->toDateString());
                $this->preLeaves[$k] ??= $leave;
            }
        }

        foreach (AttendanceLog::whereIn('employee_id', $ids)->whereBetween('punch_date', [$from, $to])->orderBy('punch_time')->get() as $log) {
            $this->preLogs[$this->key($log->employee_id, $log->punch_date)][] = $log;
        }

        // latest() per employee/date: ordering ascending and overwriting leaves the newest.
        foreach (TimeCorrection::whereIn('employee_id', $ids)->where('status', 'approved')->whereBetween('date', [$from, $to])->orderBy('created_at')->orderBy('id')->get() as $tc) {
            $this->preCorrections[$this->key($tc->employee_id, $tc->date)] = $tc;
        }

        foreach (\App\Models\EmployeeSchedule::whereIn('employee_id', $ids)->with('schedule')->orderByDesc('effective_from')->get() as $assignment) {
            $this->preSchedules[$assignment->employee_id][] = $assignment;
        }

        $this->preloaded = true;
    }

    private function key(int|string $employeeId, mixed $date): string
    {
        return ((int) $employeeId).'|'.Carbon::parse($date)->toDateString();
    }

    private function isPreloadedFor(int $employeeId, string $date): bool
    {
        return $this->preloaded
            && isset($this->preloadedEmployees[$employeeId])
            && $date >= $this->preloadFrom
            && $date <= $this->preloadTo;
    }

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
        $holiday = $this->holidayFor($date);

        if ($holiday) {
            return $this->emptyResult($holiday->name);
        }

        $cached = $this->isPreloadedFor($employee->id, $date);
        $cacheKey = $cached ? $this->key($employee->id, $date) : '';

        // 2. Individual day-off
        $hasDayOff = $cached
            ? isset($this->preDayOffs[$cacheKey])
            : DayOff::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->exists();

        if ($hasDayOff) {
            return $this->emptyResult('Day-off');
        }

        // 3. Approved leave
        $leave = $cached
            ? ($this->preLeaves[$cacheKey] ?? null)
            : LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

        if ($leave) {
            return $this->emptyResult($leave->leaveType->name);
        }

        $logs = $cached
            ? collect($this->preLogs[$cacheKey] ?? [])
            : AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('punch_date', $date)
                ->orderBy('punch_time')
                ->get();

        // 4. Approved time correction — may supplement or replace biometric punches
        $correction = $cached
            ? ($this->preCorrections[$cacheKey] ?? null)
            : TimeCorrection::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('date', $date)
                ->latest()
                ->first();

        if ($logs->isEmpty() && ! $correction) {
            return $this->emptyResult('Absent');
        }

        $boundary = Carbon::parse($date . ' 12:30:00');

        $amLogs = $logs->filter(fn ($l) => $l->punch_time->lt($boundary))->values();
        $pmLogs = $logs->filter(fn ($l) => $l->punch_time->gte($boundary))->values();

        // A short lunch break that falls entirely before the boundary (e.g. out
        // at 12:03, back in at 12:11 — both under 12:30) leaves 3+ punches in
        // the AM bucket (in, lunch-out, lunch-in) and only the final checkout in
        // the PM bucket. Left alone, that makes the checkout look like a second
        // "check-in" with no matching checkout, and the day comes back
        // "Incomplete". A normal AM session is just [in, out], so anything past
        // the first two AM punches actually belongs to the PM session.
        if ($amLogs->count() > 2) {
            $overflow = $amLogs->slice(2)->values();
            $amLogs   = $amLogs->slice(0, 2)->values();
            $pmLogs   = $overflow->concat($pmLogs)->values();
        }

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

        $assignment = $cached
            ? collect($this->preSchedules[$employee->id] ?? [])
                ->first(fn ($a) => Carbon::parse($a->effective_from)->toDateString() <= $date
                    && ($a->effective_to === null || Carbon::parse($a->effective_to)->toDateString() >= $date))
            : \App\Models\EmployeeSchedule::where('employee_id', $employee->id)
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

    /**
     * processDay() for calendars and monthly summaries: days that haven't
     * happened yet are "Upcoming", and a no-punch day that is the employee's
     * scheduled rest day (same rule as payroll) is "Day-off" rather than "Absent".
     * Nursing staff rotate shifts, so only their explicit day-offs count.
     */
    public function processDayForDisplay(Employee $employee, string $date): array
    {
        $day = Carbon::parse($date);

        if ($day->gt(now('Asia/Manila')->startOfDay())) {
            return $this->emptyResult('Upcoming');
        }

        $result = $this->processDay($employee, $date);

        if ($result['status'] === 'Absent' && $this->isScheduledRestDay($employee, $day)) {
            $result['status'] = 'Day-off';
        }

        return $result;
    }

    /**
     * Sunday, plus weekday_off (or Saturday when unset) for non-probationary
     * staff — unless the employee swapped that rest day away (DayOffSwap).
     */
    public function isScheduledRestDay(Employee $employee, CarbonInterface $day): bool
    {
        if (strcasecmp(trim((string) $employee->department), 'Nursing') === 0) {
            return false;
        }

        $scheduled = match (true) {
            $day->isSunday()                                             => true,
            ($employee->employment_type ?? 'regular') === 'probationary' => false,
            $employee->weekday_off !== null                              => $day->dayOfWeek === (int) $employee->weekday_off,
            default                                                      => $day->isSaturday(),
        };

        return $scheduled && ! DayOffSwap::isSwappedWorkday($employee->id, $day);
    }

    /**
     * Resolve every date in [$from, $to] into the holiday cache using two queries,
     * instead of letting holidayFor() spend up to two queries per distinct date.
     */
    private function warmHolidayCache(string $from, string $to): void
    {
        $fixed = Holiday::whereBetween('date', [$from, $to])
            ->get()
            ->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        $recurring = Holiday::where('is_recurring', true)
            ->get()
            ->keyBy(fn ($h) => Carbon::parse($h->date)->format('m-d'));

        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            $key = $d->toDateString();

            if (array_key_exists($key, $this->holidayCache)) {
                continue;
            }

            $this->holidayCache[$key] = $fixed->get($key) ?? $recurring->get($d->format('m-d'));
        }
    }

    /**
     * Fixed-date holiday for $date, else a recurring holiday matching its month+day.
     */
    private function holidayFor(string $date): ?Holiday
    {
        if (array_key_exists($date, $this->holidayCache)) {
            return $this->holidayCache[$date];
        }

        $holiday = Holiday::whereDate('date', $date)->first();

        if (! $holiday) {
            $parsed = Carbon::parse($date);
            $holiday = Holiday::where('is_recurring', true)
                ->whereMonth('date', $parsed->month)
                ->whereDay('date', $parsed->day)
                ->first();
        }

        return $this->holidayCache[$date] = $holiday;
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
