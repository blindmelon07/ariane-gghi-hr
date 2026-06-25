<?php

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PayrollPeriod;
use App\Models\SalaryDetail;
use App\Models\User;
use App\Services\PhilippinePayrollService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

/*
 * Sample payslip test for Bayoca Cedric (probationary)
 *
 * Period:  Mon Jun 22 – Sat Jun 27 2026  (6 calendar days, all Mon-Sat = probationary working days)
 * Day-off: Thursday Jun 25               (individual day-off record)
 * Holiday: Saturday Jun 27               ("Company Foundation Day" — company-wide holiday)
 *
 * Attendance punches (08:00 in / 12:00 out / 13:00 in / 17:00 out):
 *   Mon Jun 22 ✓ present
 *   Tue Jun 23 ✓ present
 *   Wed Jun 24 ✓ present
 *   Thu Jun 25   day-off  (no punches)
 *   Fri Jun 26 ✓ present
 *   Sat Jun 27   holiday  (no punches)
 *
 * Expected:
 *   working_days  = 4  (6 - 1 day-off - 1 holiday)
 *   days_present  = 4
 *   days_absent   = 0  → no absent deduction
 *   basic_pay     = 4 × 538.19 = 2,152.76
 *   hazard_pay    = 600.00
 *   net_pay       = 2,152.76 + 600.00 = 2,752.76  (no deductions in test DB)
 */

// ── Helpers ───────────────────────────────────────────────────────────────────

function bayocaUser(): User
{
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => 'employee']);
    $u->syncRoles('employee');
    return $u;
}

function bayocaEmployee(): Employee
{
    return Employee::create([
        'emp_code'        => 'CEDRIC',
        'first_name'      => 'Cedric',
        'last_name'       => 'Bayoca',
        'is_active'       => true,
        'employment_type' => 'probationary',
        'weekday_off'     => null, // Sunday only
        'department'      => 'Information Technology',
    ]);
}

function bayocaSalary(Employee $emp): SalaryDetail
{
    return SalaryDetail::create([
        'employee_id'  => $emp->id,
        'rate_type'    => 'monthly',
        'basic_salary' => 13993.00,
        'daily_rate'   => 538.19,
        'hourly_rate'  => 57.50,
        'hazard_pay'   => 600.00,
        'is_active'    => true,
    ]);
}

function bayocaPeriod(): PayrollPeriod
{
    return PayrollPeriod::create([
        'name'        => 'June 22–27 2026 (Test)',
        'cutoff_type' => 'custom',
        'start_date'  => '2026-06-22',
        'end_date'    => '2026-06-27',
        'status'      => 'draft',
    ]);
}

function punchDay(Employee $emp, string $date): void
{
    $punches = [
        ['punch_state' => 0, 'time' => '08:00:00'], // AM in
        ['punch_state' => 1, 'time' => '12:00:00'], // AM out
        ['punch_state' => 0, 'time' => '13:00:00'], // PM in
        ['punch_state' => 1, 'time' => '17:00:00'], // PM out
    ];

    foreach ($punches as $p) {
        AttendanceLog::create([
            'employee_id' => $emp->id,
            'emp_code'    => $emp->emp_code,
            'punch_time'  => Carbon::parse("{$date} {$p['time']}"),
            'punch_date'  => $date,
            'punch_state' => $p['punch_state'],
            'verify_type' => 1,
            'is_processed'=> false,
        ]);
    }
}

// ── The payslip test ──────────────────────────────────────────────────────────

describe('Bayoca Cedric sample payslip', function () {
    beforeEach(function () {
        $adminUser = bayocaUser(); // needed for created_by FK
        $this->emp    = bayocaEmployee();
        $this->salary = bayocaSalary($this->emp);
        $this->period = bayocaPeriod();

        // Punches: Mon/Tue/Wed/Fri (no punch on Thu day-off and Sat holiday)
        punchDay($this->emp, '2026-06-22'); // Mon
        punchDay($this->emp, '2026-06-23'); // Tue
        punchDay($this->emp, '2026-06-24'); // Wed
        punchDay($this->emp, '2026-06-26'); // Fri

        // Day-off: Thursday Jun 25
        DayOff::create([
            'employee_id' => $this->emp->id,
            'date'        => '2026-06-25',
            'type'        => 'rest_day',
            'created_by'  => $adminUser->id,
        ]);

        // Holiday: Saturday Jun 27
        Holiday::create([
            'date'         => '2026-06-27',
            'name'         => 'Company Foundation Day',
            'type'         => 'regular',
            'is_recurring' => false,
            'created_by'   => $adminUser->id,
        ]);

        $this->payslip = app(PhilippinePayrollService::class)
            ->computePayslip($this->emp, $this->period);
    });

    // ── Working days count ────────────────────────────────────────────────────

    it('counts 4 working days (6 days minus 1 day-off and 1 holiday)', function () {
        expect($this->payslip['working_days'])->toBe(4);
    });

    // ── Attendance ────────────────────────────────────────────────────────────

    it('counts 4 days present (Mon Tue Wed Fri)', function () {
        expect((int) $this->payslip['days_present'])->toBe(4);
    });

    it('counts 0 days absent (day-off and holiday are not absences)', function () {
        expect((float) $this->payslip['days_absent'])->toBe(0.0);
    });

    it('has zero absent deduction', function () {
        expect((float) $this->payslip['absent_deduction'])->toBe(0.0);
    });

    // ── Pay ───────────────────────────────────────────────────────────────────

    it('basic pay = 4 days × ₱538.19 daily rate = ₱2,152.76', function () {
        expect((float) $this->payslip['basic_pay'])->toBe(2152.76);
    });

    it('gross pay equals basic pay when no OT and no late', function () {
        expect((float) $this->payslip['gross_pay'])->toBe(2152.76);
    });

    it('hazard pay = ₱600.00', function () {
        expect((float) $this->payslip['hazard_pay'])->toBe(600.0);
    });

    it('total allowances = ₱600.00 (hazard only)', function () {
        expect((float) $this->payslip['total_allowances'])->toBe(600.0);
    });

    it('no deductions in a fresh test database', function () {
        expect((float) $this->payslip['total_deductions'])->toBe(0.0);
    });

    it('net pay = gross pay + allowances = ₱2,152.76 + ₱600.00 = ₱2,752.76', function () {
        expect((float) $this->payslip['net_pay'])->toBe(2752.76);
    });

    // ── Day-off integration: Thursday attendance shows Day-off ────────────────

    it('Thursday Jun 25 attendance status is Day-off (not Absent)', function () {
        $processor = app(\App\Services\AttendanceProcessorService::class);
        $result    = $processor->processDay($this->emp, '2026-06-25');

        expect($result['status'])->toBe('Day-off');
    });

    // ── Holiday integration: Saturday attendance shows holiday name ───────────

    it('Saturday Jun 27 attendance status is the holiday name', function () {
        $processor = app(\App\Services\AttendanceProcessorService::class);
        $result    = $processor->processDay($this->emp, '2026-06-27');

        expect($result['status'])->toBe('Company Foundation Day');
    });

    // ── Worked days show correct status ──────────────────────────────────────

    it('Monday Jun 22 attendance status is Present', function () {
        $processor = app(\App\Services\AttendanceProcessorService::class);
        $result    = $processor->processDay($this->emp, '2026-06-22');

        expect($result['status'])->toBe('Present')
            ->and($result['hours_worked'])->toBeGreaterThan(0);
    });
});
