<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OtherDeduction;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use Illuminate\Support\Carbon;
use App\Services\AttendanceProcessorService;

class PhilippinePayrollService
{
    /**
     * SSS 2024 contribution table: [min_salary, max_salary, employee_share]
     */
    protected array $sssBrackets = [
        [0, 4249.99, 180.00],
        [4250, 4749.99, 202.50],
        [4750, 5249.99, 225.00],
        [5250, 5749.99, 247.50],
        [5750, 6249.99, 270.00],
        [6250, 6749.99, 292.50],
        [6750, 7249.99, 315.00],
        [7250, 7749.99, 337.50],
        [7750, 8249.99, 360.00],
        [8250, 8749.99, 382.50],
        [8750, 9249.99, 405.00],
        [9250, 9749.99, 427.50],
        [9750, 10249.99, 450.00],
        [10250, 10749.99, 472.50],
        [10750, 11249.99, 495.00],
        [11250, 11749.99, 517.50],
        [11750, 12249.99, 540.00],
        [12250, 12749.99, 562.50],
        [12750, 13249.99, 585.00],
        [13250, 13749.99, 607.50],
        [13750, 14249.99, 630.00],
        [14250, 14749.99, 652.50],
        [14750, 15249.99, 675.00],
        [15250, 15749.99, 697.50],
        [15750, 16249.99, 720.00],
        [16250, 16749.99, 742.50],
        [16750, 17249.99, 765.00],
        [17250, 17749.99, 787.50],
        [17750, 18249.99, 810.00],
        [18250, 18749.99, 832.50],
        [18750, 19249.99, 855.00],
        [19250, 19749.99, 877.50],
        [19750, 20249.99, 900.00],
        [20250, 20749.99, 922.50],
        [20750, 21249.99, 945.00],
        [21250, 21749.99, 967.50],
        [21750, 22249.99, 990.00],
        [22250, 22749.99, 1012.50],
        [22750, 23249.99, 1035.00],
        [23250, 23749.99, 1057.50],
        [23750, 24249.99, 1080.00],
        [24250, 24749.99, 1102.50],
        [24750, 25249.99, 1125.00],
        [25250, 25749.99, 1147.50],
        [25750, 26249.99, 1170.00],
        [26250, 26749.99, 1192.50],
        [26750, 27249.99, 1215.00],
        [27250, 27749.99, 1237.50],
        [27750, 28249.99, 1260.00],
        [28250, 28749.99, 1282.50],
        [28750, 29249.99, 1305.00],
        [29250, 29749.99, 1327.50],
        [29750, 99999999, 1350.00],
    ];

    /**
     * Compute SSS employee contribution (monthly).
     */
    public function computeSSS(float $monthlySalary): float
    {
        foreach ($this->sssBrackets as [$min, $max, $share]) {
            if ($monthlySalary >= $min && $monthlySalary <= $max) {
                return $share;
            }
        }

        return 1350.00;
    }

    /**
     * Compute PhilHealth employee share (monthly).
     * Rate: 5% of salary, employee = 50%. Min ₱500/mo total (₱250 employee), Max ₱5000/mo total (₱2500 employee).
     */
    public function computePhilHealth(float $monthlySalary): float
    {
        $total    = $monthlySalary * 0.05;
        $total    = max(500, min(5000, $total));
        $employee = $total / 2;

        return round($employee, 2);
    }

    /**
     * Compute Pag-IBIG employee share (monthly).
     * 2% of salary, max ₱200/month.
     */
    public function computePagIBIG(float $monthlySalary): float
    {
        return min(200, round($monthlySalary * 0.02, 2));
    }

    /**
     * Compute withholding tax using TRAIN Law 2024 semi-monthly brackets.
     */
    public function computeTax(float $semiMonthlyTaxable): float
    {
        if ($semiMonthlyTaxable <= 10417) {
            return 0;
        }

        if ($semiMonthlyTaxable <= 16667) {
            return round(($semiMonthlyTaxable - 10417) * 0.20, 2);
        }

        if ($semiMonthlyTaxable <= 33333) {
            return round(1250 + ($semiMonthlyTaxable - 16667) * 0.25, 2);
        }

        if ($semiMonthlyTaxable <= 83333) {
            return round(5417 + ($semiMonthlyTaxable - 33333) * 0.30, 2);
        }

        if ($semiMonthlyTaxable <= 333333) {
            return round(20833 + ($semiMonthlyTaxable - 83333) * 0.32, 2);
        }

        return round(108333 + ($semiMonthlyTaxable - 333333) * 0.35, 2);
    }

    /**
     * Compute the full payslip breakdown for an employee and payroll period.
     */
    public function computePayslip(Employee $employee, PayrollPeriod $period): array
    {
        $salary = $employee->salaryDetail;

        if (!$salary) {
            return $this->emptyPayslip();
        }

        $dailyRate   = (float) $salary->daily_rate;
        $hourlyRate  = (float) $salary->hourly_rate;
        $basicSalary = (float) $salary->basic_salary;

        // Allowances (semi-monthly amounts)
        $hazardPay         = round((float) ($salary->hazard_pay ?? 0), 2);
        $riceAllowance     = round((float) ($salary->rice_allowance ?? 0), 2);
        $medicalAllowance  = round((float) ($salary->medical_allowance ?? 0), 2);
        $commodityAllowance = round((float) ($salary->commodity_allowance ?? 0), 2);
        $otherAllowanceAmt = round((float) ($salary->other_allowance ?? 0), 2);
        $totalAllowances   = round($hazardPay + $riceAllowance + $medicalAllowance + $commodityAllowance + $otherAllowanceAmt, 2);

        // Count working days in period based on employment type:
        // probationary = Mon–Sat (exclude Sundays only)
        // regular      = Mon–Fri (exclude Saturdays and Sundays)
        // Also exclude individual day-offs and company holidays — those are paid
        // non-working days that should not cause an absent deduction.
        $isProbationary = ($employee->employment_type ?? 'regular') === 'probationary';
        $isNurse        = $this->isNurse($employee);

        // Preload day-off dates for this employee in the period
        $dayOffDates = DayOff::where('employee_id', $employee->id)
            ->whereDate('date', '>=', $period->start_date)
            ->whereDate('date', '<=', $period->end_date)
            ->pluck('date')
            ->map(fn ($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
            ->toArray();

        // Preload holiday dates (one-time + recurring) in the period
        $holidayDates = $this->getHolidayDates($period->start_date, $period->end_date);

        // Nursing staff rotate across 8/9/12/13-hour shifts (see ScheduleSeeder),
        // so a calendar weekday count doesn't apply to them. Instead they're held
        // to a fixed required-hours quota per cutoff (see getNurseRequiredHours()),
        // expressed here in 8-hour "day equivalents" so it flows through the same
        // basic-pay / absent-deduction formulas as everyone else.
        $requiredHours = $isNurse ? $this->getNurseRequiredHours($employee, $period) : null;

        $workingDays = 0;
        if ($isNurse) {
            $workingDays = round($requiredHours / 8, 2);
        } else {
            for ($d = Carbon::parse($period->start_date); $d->lte($period->end_date); $d->addDay()) {
                if ($d->isSunday()) {
                    continue;
                }
                if (! $isProbationary && $d->isSaturday()) {
                    continue;
                }
                if (in_array($d->toDateString(), $dayOffDates, true)) {
                    continue;
                }
                if (in_array($d->toDateString(), $holidayDates, true)) {
                    continue;
                }
                $workingDays++;
            }
        }

        // Distinct dates with at least one punch in the period
        $punchDates = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('punch_date', [$period->start_date, $period->end_date])
            ->distinct()
            ->pluck('punch_date')
            ->map(fn ($d) => \is_string($d) ? $d : $d->toDateString())
            ->unique()
            ->values();

        // Count approved leave days within period
        $approvedLeaveDays = $this->getApprovedLeaveDays($employee->id, $period->start_date, $period->end_date);

        // Process each punch day through AttendanceProcessorService.
        // Days where PM In exists but PM Out is missing are "incomplete" and excluded from payroll.
        $processor             = app(AttendanceProcessorService::class);
        $daysPresent           = 0;
        $basicPay              = 0.0;
        $totalLateMinutes      = 0;
        $totalUndertimeMinutes = 0;
        $renderedHours         = 0.0;

        foreach ($punchDates as $dateStr) {
            $day = $processor->processDay($employee, $dateStr);

            // Incomplete: PM session opened but never closed
            if ($day['pm_time_in'] !== null && $day['pm_time_out'] === null) {
                continue;
            }

            // Incomplete: only a single punch with no time-out at all
            if ($day['time_in'] !== null && $day['time_out'] === null) {
                continue;
            }

            // Absent (no punches matched — shouldn't happen since we queried punch dates, but guard anyway)
            if ($day['time_in'] === null) {
                continue;
            }

            $daysPresent++;
            $totalLateMinutes      += $day['minutes_late'];
            $totalUndertimeMinutes += $day['minutes_undertime'];
            $renderedHours         += $day['hours_worked'];
        }

        // Basic pay = FULL period pay (working days × daily rate)
        // Absent deduction = absent days × 8 hrs × hourly rate (hours-based, matches HR payslip)
        $basicPay = round($workingDays * $dailyRate, 2);

        if ($isNurse) {
            // Nurses' shifts vary in length, so "absence" is measured in total hours
            // short of the cutoff's required-hours quota (approved leave credited at
            // a flat 8 hrs/day), not in whole missed calendar days.
            $creditedHours = round($renderedHours + ($approvedLeaveDays * 8), 2);
            $daysAbsent    = round(max(0, $requiredHours - $creditedHours) / 8, 2);
        } else {
            $daysAbsent = max(0, $workingDays - $daysPresent - $approvedLeaveDays);
        }

        $absentDeduction = round($daysAbsent * 8 * $hourlyRate, 2);

        // Overtime: only approved OT requests count — not raw attendance
        $approvedOt    = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->get();
        $overtimeHours = round($approvedOt->sum(fn ($r) => (float) ($r->approved_hours ?? $r->requested_hours)), 2);
        $overtimePay   = round($overtimeHours * $hourlyRate * 1.25, 2);

        // Round to 2-decimal hours FIRST (matches HR department's lookup table)
        // e.g. 25 min → 0.42 hrs (table value) → 0.42 × hourlyRate = exact HR result
        $lateHours          = round($totalLateMinutes / 60, 2);
        $undertimeHours     = round($totalUndertimeMinutes / 60, 2);
        $lateDeduction      = round($lateHours * $hourlyRate, 2);
        $undertimeDeduction = round($undertimeHours * $hourlyRate, 2);

        $grossPay = round($basicPay - $absentDeduction + $overtimePay - $lateDeduction - $undertimeDeduction, 2);

        // Load active deductions filtered by the period's cutoff schedule:
        // semi_monthly_1 → apply 'both' and '1st' deductions
        // semi_monthly_2 → apply 'both' and '2nd' deductions
        // monthly        → apply all deductions
        $applicableCutoffs = match ($period->cutoff_type) {
            'semi_monthly_1' => ['both', '1st'],
            'semi_monthly_2' => ['both', '2nd'],
            'monthly'        => ['both', '1st', '2nd'],
            default          => ['both'],   // custom period: apply only "Both Cutoffs" deductions
        };

        $activeDeductions = OtherDeduction::with('deductionType')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereIn('cutoff_schedule', $applicableCutoffs)
            ->get();

        // Group manual deductions by type for payslip breakdown.
        // All amounts come from manually entered deduction records — no auto-calculation.
        $isSss = fn ($d) => preg_match('/^SSS/i',          $d->deductionType?->code ?? '');
        $isPhc = fn ($d) => preg_match('/^PHC|^PHIL/i',    $d->deductionType?->code ?? '');
        $isPag = fn ($d) => preg_match('/^PAG/i',           $d->deductionType?->code ?? '');
        $isTax = fn ($d) => preg_match('/^TAX|^WHT|^WTAX/i', $d->deductionType?->code ?? '');

        $sss        = round((float) $activeDeductions->filter($isSss)->sum('amount_per_cutoff'), 2);
        $philhealth = round((float) $activeDeductions->filter($isPhc)->sum('amount_per_cutoff'), 2);
        $pagibig    = round((float) $activeDeductions->filter($isPag)->sum('amount_per_cutoff'), 2);
        $tax        = round((float) $activeDeductions->filter($isTax)->sum('amount_per_cutoff'), 2);

        $otherDeductions       = $activeDeductions->reject(fn ($d) => $isSss($d) || $isPhc($d) || $isPag($d) || $isTax($d));
        $otherDeductionsAmount = round((float) $otherDeductions->sum('amount_per_cutoff'), 2);

        $totalDeductions = round($sss + $philhealth + $pagibig + $tax + $otherDeductionsAmount, 2);

        // Net pay = basic salary (gross pay after attendance deductions) + allowances - statutory deductions
        $netPay = round($grossPay + $totalAllowances - $totalDeductions, 2);

        return [
            'working_days'         => $workingDays,
            'days_present'         => $daysPresent,
            'days_absent'          => $daysAbsent,
            'absent_deduction'     => $absentDeduction,
            'basic_pay'            => $basicPay,
            'overtime_hours'       => $overtimeHours,
            'overtime_pay'         => $overtimePay,
            'late_minutes'         => $totalLateMinutes,
            'late_deduction'       => $lateDeduction,
            'undertime_minutes'    => $totalUndertimeMinutes,
            'undertime_deduction'  => $undertimeDeduction,
            'gross_pay'            => $grossPay,
            'hazard_pay'           => $hazardPay,
            'rice_allowance'       => $riceAllowance,
            'medical_allowance'    => $medicalAllowance,
            'commodity_allowance'  => $commodityAllowance,
            'other_allowance'      => $otherAllowanceAmt,
            'total_allowances'     => $totalAllowances,
            'sss_deduction'        => $sss,
            'philhealth_deduction' => $philhealth,
            'pagibig_deduction'    => $pagibig,
            'tax_deduction'        => $tax,
            'other_deductions'     => $otherDeductionsAmount,
            'total_deductions'     => $totalDeductions,
            'net_pay'              => $netPay,
        ];
    }

    /**
     * Required duty hours per cutoff for Nursing staff, keyed by employment type.
     * The higher figure applies only to the 2nd-half cutoff of a 31-day month
     * (the extra day 31 adds one more 8-hour shift to the quota).
     */
    protected const NURSE_REQUIRED_HOURS = [
        'regular'      => ['base' => 88, 'month_31' => 96],
        'probationary' => ['base' => 104, 'month_31' => 112],
    ];

    /**
     * Nursing staff are held to a fixed required-hours quota per cutoff instead
     * of a calendar weekday count, since their shifts rotate across 8/9/12/13-hour
     * lengths (see ScheduleSeeder). Identified by department name.
     */
    protected function isNurse(Employee $employee): bool
    {
        return strcasecmp(trim((string) $employee->department), 'Nursing') === 0;
    }

    /**
     * Determine the required duty hours for a nurse's payroll period.
     */
    protected function getNurseRequiredHours(Employee $employee, PayrollPeriod $period): int
    {
        $type = ($employee->employment_type ?? 'regular') === 'probationary' ? 'probationary' : 'regular';
        $rule = self::NURSE_REQUIRED_HOURS[$type];

        $monthHas31Days = Carbon::parse($period->start_date)->daysInMonth === 31;

        return match ($period->cutoff_type) {
            // Only the 2nd-half cutoff absorbs the extra day of a 31-day month.
            'semi_monthly_2' => $monthHas31Days ? $rule['month_31'] : $rule['base'],
            // Monthly cutoffs cover both halves.
            'monthly'        => $rule['base'] * 2 + ($monthHas31Days ? 8 : 0),
            // 1st-half and custom periods use the base figure.
            default          => $rule['base'],
        };
    }

    /**
     * Holiday dates (one-time + recurring) that fall within a date range.
     */
    protected function getHolidayDates($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        // One-time holidays in range
        $dates = Holiday::where('is_recurring', false)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->pluck('date')
            ->map(fn ($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
            ->toArray();

        // Recurring holidays — match same month+day in any year within range
        $recurring = Holiday::where('is_recurring', true)->get();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            foreach ($recurring as $h) {
                if ($h->date->month === $d->month && $h->date->day === $d->day) {
                    $dates[] = $d->toDateString();
                    break;
                }
            }
        }

        return array_unique($dates);
    }

    /**
     * Count approved leave days within a date range (excludes Sundays).
     */
    protected function getApprovedLeaveDays(int $employeeId, $startDate, $endDate): float
    {
        $leaves = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        $totalDays = 0;

        foreach ($leaves as $leave) {
            $from = Carbon::parse(max($leave->start_date, $startDate));
            $to   = Carbon::parse(min($leave->end_date, $endDate));

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                if (!$d->isSunday()) {
                    $totalDays++;
                }
            }
        }

        return $totalDays;
    }

    protected function emptyPayslip(): array
    {
        return [
            'working_days' => 0, 'days_present' => 0, 'days_absent' => 0,
            'absent_deduction' => 0,
            'basic_pay' => 0, 'overtime_hours' => 0, 'overtime_pay' => 0,
            'late_minutes' => 0, 'late_deduction' => 0,
            'undertime_minutes' => 0, 'undertime_deduction' => 0,
            'gross_pay' => 0,
            'hazard_pay' => 0, 'rice_allowance' => 0, 'medical_allowance' => 0,
            'commodity_allowance' => 0, 'other_allowance' => 0, 'total_allowances' => 0,
            'sss_deduction' => 0, 'philhealth_deduction' => 0,
            'pagibig_deduction' => 0, 'tax_deduction' => 0, 'other_deductions' => 0,
            'total_deductions' => 0, 'net_pay' => 0,
        ];
    }
}
