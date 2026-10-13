<?php

use App\Models\Employee;
use App\Services\AttendanceProcessorService;

beforeEach(fn () => $this->travelTo('2026-10-14 10:00:00'));   // a Wednesday (02:00 UTC → 10:00 Manila)

function displayEmp(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create($attrs + ['emp_code' => "DSP{$n}", 'first_name' => 'D', 'last_name' => "E{$n}", 'is_active' => true, 'employment_type' => 'regular', 'department' => 'Administration']);
}

it('does not mark future days absent', function () {
    $status = app(AttendanceProcessorService::class)->processDayForDisplay(displayEmp(), '2026-10-20')['status'];
    expect($status)->toBe('Upcoming');
});

it('shows rest days as day-off instead of absent', function () {
    $svc = app(AttendanceProcessorService::class);

    expect($svc->processDayForDisplay(displayEmp(), '2026-10-11')['status'])->toBe('Day-off')                       // Sunday
        ->and($svc->processDayForDisplay(displayEmp(), '2026-10-10')['status'])->toBe('Day-off')                     // Saturday, regular
        ->and($svc->processDayForDisplay(displayEmp(['weekday_off' => 1]), '2026-10-12')['status'])->toBe('Day-off') // their Monday off
        ->and($svc->processDayForDisplay(displayEmp(['weekday_off' => 1]), '2026-10-10')['status'])->toBe('Absent')  // works Saturdays
        ->and($svc->processDayForDisplay(displayEmp(['employment_type' => 'probationary']), '2026-10-10')['status'])->toBe('Absent')
        ->and($svc->processDayForDisplay(displayEmp(), '2026-10-13')['status'])->toBe('Absent');                     // a past workday
});

it('leaves nursing staff rest days to their explicit day-offs', function () {
    $status = app(AttendanceProcessorService::class)->processDayForDisplay(displayEmp(['department' => 'Nursing']), '2026-10-11')['status'];
    expect($status)->toBe('Absent');
});
