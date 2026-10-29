<?php

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SalaryDetail;
use App\Services\PhilippinePayrollService;
use Carbon\Carbon;

/*
 * Monthly-rate payslip, checked against the HR payslip for Marjo Cortez
 * (Oct 11–25, 2026): monthly rate 16,658.00, 34 minutes late.
 *
 *   Regular Shift  = 16,658 ÷ 2                          = 8,329.00
 *   Salary/hour    = 16,658 × 12 ÷ 365 ÷ 8               = 68.46
 *   Late           = 34 min → 0.57 hrs × 68.46           = 39.02
 *
 * The stored hourly_rate (80.09 = daily ÷ 8) is deliberately wrong: the
 * payroll must derive salary per hour from the monthly rate instead.
 */

function monthlyRateEmployee(): Employee
{
    $emp = Employee::create([
        'emp_code'        => 'MONTHLY',
        'first_name'      => 'Marjo',
        'last_name'       => 'Cortez',
        'is_active'       => true,
        'employment_type' => 'regular',
        'weekday_off'     => 1,
        'department'      => 'Procurement',
    ]);

    SalaryDetail::create([
        'employee_id'  => $emp->id,
        'rate_type'    => 'monthly',
        'basic_salary' => 16658.00,
        'daily_rate'   => 640.69,
        'hourly_rate'  => 80.09,
        'is_active'    => true,
    ]);

    return $emp;
}

function monthlyRatePunch(Employee $emp, string $date, string $timeIn): void
{
    foreach ([[0, $timeIn], [1, '12:00:00'], [0, '13:00:00'], [1, '17:00:00']] as [$state, $time]) {
        AttendanceLog::create([
            'employee_id' => $emp->id,
            'emp_code'    => $emp->emp_code,
            'punch_time'  => Carbon::parse("{$date} {$time}"),
            'punch_date'  => $date,
            'punch_state' => $state,
        ]);
    }
}

describe('monthly-rate payslip', function () {
    beforeEach(function () {
        $this->emp = monthlyRateEmployee();

        // Tue–Sat Oct 20–24 (Monday is the rest day); 34 min late on Tuesday.
        monthlyRatePunch($this->emp, '2026-10-20', '08:34:00');
        foreach (['2026-10-21', '2026-10-22', '2026-10-23', '2026-10-24'] as $date) {
            monthlyRatePunch($this->emp, $date, '08:00:00');
        }

        $this->period = PayrollPeriod::create([
            'name'        => 'Oct 19–24 2026 (Test)',
            'cutoff_type' => 'semi_monthly_2',
            'start_date'  => '2026-10-19',
            'end_date'    => '2026-10-24',
            'status'      => 'draft',
        ]);

        $this->payslip = app(PhilippinePayrollService::class)->computePayslip($this->emp, $this->period);
    });

    it('pays half the monthly rate as the regular shift, whatever the day count', function () {
        expect((float) $this->payslip['basic_pay'])->toBe(8329.0);
    });

    it('charges late as decimal hours × salary per hour derived from the monthly rate', function () {
        expect($this->payslip['late_minutes'])->toBe(34)
            ->and((float) $this->payslip['late_deduction'])->toBe(39.02);
    });

    it('nets the late deduction out of the regular shift', function () {
        expect((float) $this->payslip['days_absent'])->toBe(0.0)
            ->and((float) $this->payslip['gross_pay'])->toBe(8289.98);
    });
});
