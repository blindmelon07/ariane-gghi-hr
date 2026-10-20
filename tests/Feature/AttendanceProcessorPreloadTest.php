<?php

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TimeCorrection;
use App\Services\AttendanceProcessorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * The attendance/late reports walk every employee across a date range. Without
 * preload() that is five queries per employee-day (~7k queries, >20s, and the page
 * timed out). preload() bulk-loads the same data up front, so these tests pin the
 * contract that matters: preloaded results must be identical to unpreloaded ones.
 */

function processorEmployee(string $code, int $weekdayOff = 0): Employee
{
    return Employee::create([
        'emp_code'        => $code,
        'first_name'      => 'Pre',
        'last_name'       => 'Load'.$code,
        'is_active'       => true,
        'employment_type' => 'regular',
        'weekday_off'     => $weekdayOff,
    ]);
}

function processorPunch(Employee $e, string $date, string $time): void
{
    AttendanceLog::create([
        'employee_id' => $e->id,
        'emp_code'    => $e->emp_code,
        'punch_date'  => $date,
        'punch_time'  => $date.' '.$time,
    ]);
}

/** Builds a range that exercises every early-return branch in processDay(). */
function seedProcessorScenario(): array
{
    $a = processorEmployee('PRE-A');
    $b = processorEmployee('PRE-B');

    // Holiday — employee-independent branch
    Holiday::create(['name' => 'Test Holiday', 'date' => '2026-10-05', 'is_recurring' => false]);

    // Day-off for A only
    DayOff::create(['employee_id' => $a->id, 'date' => '2026-10-06']);

    // Approved leave for B only, spanning several days
    $type = LeaveType::create([
        'name'              => 'Vacation Leave',
        'code'              => 'VL-PRE',
        'max_days_per_year' => 15,
    ]);
    LeaveRequest::create([
        'employee_id'  => $b->id,
        'leave_type_id' => $type->id,
        'start_date'   => '2026-10-07',
        'end_date'     => '2026-10-09',
        'total_days'   => 3,
        'reason'       => 'Family trip',
        'status'       => 'approved',
    ]);

    // Normal, late, and incomplete punch days
    processorPunch($a, '2026-10-01', '07:58:00');
    processorPunch($a, '2026-10-01', '12:05:00');
    processorPunch($a, '2026-10-01', '13:02:00');
    processorPunch($a, '2026-10-01', '17:10:00');

    processorPunch($a, '2026-10-02', '09:41:00');   // late
    processorPunch($a, '2026-10-02', '17:05:00');

    processorPunch($b, '2026-10-02', '08:00:00');   // single punch -> incomplete

    // Approved correction supplying times on a day with no punches at all
    TimeCorrection::create([
        'employee_id' => $b->id,
        'date'        => '2026-10-12',
        'am_time_in'  => '08:30:00',
        'pm_time_out' => '17:00:00',
        'reason'      => 'Biometric offline',
        'status'      => 'approved',
    ]);

    return [collect([$a, $b]), '2026-10-01', '2026-10-15'];
}

function walkRange(AttendanceProcessorService $svc, $employees, string $from, string $to): array
{
    $out = [];

    foreach ($employees as $emp) {
        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            $out[$emp->emp_code.' '.$d->toDateString()] = $svc->processDay($emp, $d->toDateString());
        }
    }

    return $out;
}

it('returns identical results whether or not the range was preloaded', function () {
    [$employees, $from, $to] = seedProcessorScenario();

    $plain = walkRange(new AttendanceProcessorService(), $employees, $from, $to);

    $preloading = new AttendanceProcessorService();
    $preloading->preload($employees->pluck('id'), $from, $to);
    $preloaded = walkRange($preloading, $employees, $from, $to);

    expect($preloaded)->toEqual($plain);

    // Sanity: the scenario really does cover the interesting branches.
    expect(collect($plain)->pluck('status')->unique()->values()->all())
        ->toContain('Test Holiday', 'Day-off', 'Vacation Leave', 'Absent');
});

it('issues dramatically fewer queries when preloaded', function () {
    [$employees, $from, $to] = seedProcessorScenario();

    DB::enableQueryLog();
    walkRange(new AttendanceProcessorService(), $employees, $from, $to);
    $plainQueries = count(DB::getQueryLog());

    DB::flushQueryLog();
    $preloading = new AttendanceProcessorService();
    $preloading->preload($employees->pluck('id'), $from, $to);
    walkRange($preloading, $employees, $from, $to);
    $preloadedQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // 2 employees x 15 days was ~100 queries before; preloading is a flat handful.
    expect($preloadedQueries)->toBeLessThan($plainQueries / 4);
});

it('falls back to querying for employees or dates outside the preloaded window', function () {
    [$employees, $from, $to] = seedProcessorScenario();

    $svc = new AttendanceProcessorService();
    $svc->preload([$employees->first()->id], $from, $to);

    // Date outside the window, and an employee that was never preloaded, must still
    // resolve correctly rather than silently coming back empty.
    $outsideDate = $svc->processDay($employees->first(), '2026-11-02');
    $otherEmp    = $svc->processDay($employees->last(), '2026-10-02');

    $plain = new AttendanceProcessorService();
    expect($outsideDate)->toEqual($plain->processDay($employees->first(), '2026-11-02'));
    expect($otherEmp)->toEqual($plain->processDay($employees->last(), '2026-10-02'));
});

it('resolves holidays once per date rather than once per employee-day', function () {
    [$employees, , ] = seedProcessorScenario();

    $svc = new AttendanceProcessorService();

    DB::enableQueryLog();
    foreach ($employees as $emp) {
        $svc->processDay($emp, '2026-10-05');   // the holiday
        $svc->processDay($emp, '2026-10-05');
    }
    $queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'holidays'))->count();
    DB::disableQueryLog();

    expect($queries)->toBe(1);
});
