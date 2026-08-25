<?php

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SalaryDetail;
use App\Models\User;
use App\Services\PhilippinePayrollService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

/*
 * Nursing staff are paid against a fixed required-hours quota per cutoff
 * instead of a calendar working-day count, since their shifts rotate across
 * 8/9/12/13-hour lengths:
 *
 *   Regular      — 88 hrs/cutoff (96 hrs on the 2nd-half cutoff of a 31-day month)
 *   Probationary — 104 hrs/cutoff (112 hrs on the 2nd-half cutoff of a 31-day month)
 */

// ── Helpers ───────────────────────────────────────────────────────────────────

function nurseUser(): User
{
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => 'employee']);
    $u->syncRoles('employee');
    return $u;
}

function nurseEmployee(string $employmentType = 'regular'): Employee
{
    return Employee::create([
        'emp_code'        => 'NURSE-' . strtoupper($employmentType) . '-' . uniqid(),
        'first_name'      => 'Test',
        'last_name'       => 'Nurse',
        'is_active'       => true,
        'employment_type' => $employmentType,
        'department'      => 'Nursing',
    ]);
}

function nurseSalary(Employee $emp): SalaryDetail
{
    return SalaryDetail::create([
        'employee_id'  => $emp->id,
        'rate_type'    => 'monthly',
        'basic_salary' => 22000.00,
        'daily_rate'   => 800.00,
        'hourly_rate'  => 100.00,
        'is_active'    => true,
    ]);
}

function nursePeriod(string $name, string $cutoffType, string $start, string $end): PayrollPeriod
{
    return PayrollPeriod::create([
        'name'        => $name,
        'cutoff_type' => $cutoffType,
        'start_date'  => $start,
        'end_date'    => $end,
        'status'      => 'draft',
    ]);
}

/** Punch an 8-hour shift (08:00-12:00, 13:00-17:00) for the given date. */
function nursePunchDay(Employee $emp, string $date): void
{
    $punches = [
        ['punch_state' => 0, 'time' => '08:00:00'],
        ['punch_state' => 1, 'time' => '12:00:00'],
        ['punch_state' => 0, 'time' => '13:00:00'],
        ['punch_state' => 1, 'time' => '17:00:00'],
    ];

    foreach ($punches as $p) {
        AttendanceLog::create([
            'employee_id' => $emp->id,
            'emp_code'    => $emp->emp_code,
            'punch_time'  => Carbon::parse("{$date} {$p['time']}"),
            'punch_date'  => $date,
            'punch_state' => $p['punch_state'],
            'verify_type' => 1,
            'is_processed' => false,
        ]);
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('Nurse required-hours quota', function () {
    beforeEach(function () {
        nurseUser(); // needed for created_by FK on related records
    });

    it('regular nurse hitting exactly 88 hrs on a 1st-half cutoff has zero absent deduction', function () {
        $emp    = nurseEmployee('regular');
        $salary = nurseSalary($emp);
        $period = nursePeriod('Jan 2026 - 1st Half', 'semi_monthly_1', '2026-01-01', '2026-01-15');

        // 11 shifts x 8 hrs = 88 hrs
        foreach (range(1, 11) as $day) {
            nursePunchDay($emp, sprintf('2026-01-%02d', $day));
        }

        $result = app(PhilippinePayrollService::class)->computePayslip($emp, $period);

        expect($result['working_days'])->toBe(11.0)
            ->and($result['days_absent'])->toBe(0.0)
            ->and($result['absent_deduction'])->toBe(0.0)
            ->and($result['basic_pay'])->toBe(8800.00); // 11 x 800 daily rate
    });

    it('regular nurse short 16 hrs incurs a proportional absent deduction', function () {
        $emp    = nurseEmployee('regular');
        $salary = nurseSalary($emp);
        $period = nursePeriod('Jan 2026 - 1st Half (shortfall)', 'semi_monthly_1', '2026-01-01', '2026-01-15');

        // 9 shifts x 8 hrs = 72 hrs, 16 hrs short of the 88 hr quota
        foreach (range(1, 9) as $day) {
            nursePunchDay($emp, sprintf('2026-01-%02d', $day));
        }

        $result = app(PhilippinePayrollService::class)->computePayslip($emp, $period);

        expect($result['days_absent'])->toBe(2.0) // 16 hrs / 8
            ->and($result['absent_deduction'])->toBe(1600.00); // 2 x 8 x 100 hourly rate
    });

    it('probationary nurse on a 31-day month 2nd-half cutoff requires 112 hrs', function () {
        $emp    = nurseEmployee('probationary');
        $salary = nurseSalary($emp);
        // January has 31 days — 2nd half cutoff bumps the quota from 104 to 112
        $period = nursePeriod('Jan 2026 - 2nd Half', 'semi_monthly_2', '2026-01-16', '2026-01-31');

        // 14 shifts x 8 hrs = 112 hrs — exactly meets the bumped quota
        foreach (range(16, 29) as $day) {
            nursePunchDay($emp, sprintf('2026-01-%02d', $day));
        }

        $result = app(PhilippinePayrollService::class)->computePayslip($emp, $period);

        expect($result['working_days'])->toBe(14.0)
            ->and($result['days_absent'])->toBe(0.0);
    });

    it('regular nurse on a 30-day month 2nd-half cutoff keeps the base 88 hr quota', function () {
        $emp    = nurseEmployee('regular');
        $salary = nurseSalary($emp);
        // April has 30 days — no 31st-day bump even on the 2nd half
        $period = nursePeriod('Apr 2026 - 2nd Half', 'semi_monthly_2', '2026-04-16', '2026-04-30');

        foreach (range(16, 26) as $day) {
            nursePunchDay($emp, sprintf('2026-04-%02d', $day));
        }

        $result = app(PhilippinePayrollService::class)->computePayslip($emp, $period);

        expect($result['working_days'])->toBe(11.0) // 88 hrs / 8, not 96
            ->and($result['days_absent'])->toBe(0.0);
    });
});
