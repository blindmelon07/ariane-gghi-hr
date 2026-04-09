<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OtherDeduction;
use App\Models\PayrollPeriod;
use Illuminate\Support\Carbon;

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

        $dailyRate  = (float) $salary->daily_rate;
        $hourlyRate = (float) $salary->hourly_rate;
        $basicSalary = (float) $salary->basic_salary;

        // Count working days in period (Mon-Sat, exclude Sunday)
        $workingDays = 0;
        for ($d = $period->start_date->copy(); $d->lte($period->end_date); $d->addDay()) {
            if (!$d->isSunday()) {
                $workingDays++;
            }
        }

        // Get attendance data for the period
        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('punch_date', [$period->start_date, $period->end_date])
            ->orderBy('punch_time')
            ->get();

        // Distinct days with at least one punch
        $punchDates = $logs->pluck('punch_date')->map(fn ($d) => $d->toDateString())->unique();

        // Count approved leave days within period
        $approvedLeaveDays = $this->getApprovedLeaveDays($employee->id, $period->start_date, $period->end_date);

        $daysPresent = $punchDates->count();
        $daysAbsent  = max(0, $workingDays - $daysPresent - $approvedLeaveDays);
        $basicPay    = round($dailyRate * $daysPresent, 2);

        // Calculate overtime, late, undertime from daily logs
        $totalOvertimeHours   = 0;
        $totalLateMinutes     = 0;
        $totalUndertimeMinutes = 0;

        foreach ($punchDates as $dateStr) {
            $dayLogs  = $logs->filter(fn ($l) => $l->punch_date->toDateString() === $dateStr)->sortBy('punch_time');
            $timeIn   = $dayLogs->first()?->punch_time;
            $timeOut  = $dayLogs->count() > 1 ? $dayLogs->last()->punch_time : null;

            if (!$timeIn) {
                continue;
            }

            $schedIn  = Carbon::parse($dateStr)->setTime(8, 0, 0);
            $schedOut = Carbon::parse($dateStr)->setTime(17, 0, 0);

            // Late
            if ($timeIn->gt($schedIn)) {
                $totalLateMinutes += (int) $timeIn->diffInMinutes($schedIn);
            }

            if ($timeOut) {
                // Undertime
                if ($timeOut->lt($schedOut)) {
                    $totalUndertimeMinutes += (int) $schedOut->diffInMinutes($timeOut);
                }

                // Overtime: hours worked beyond 8 when punch_out is after 17:00
                if ($timeOut->gt($schedOut)) {
                    $otHours = $timeOut->floatDiffInHours($schedOut);
                    $totalOvertimeHours += $otHours;
                }
            }
        }

        $overtimeHours = round($totalOvertimeHours, 2);
        $overtimePay   = round($overtimeHours * $hourlyRate * 1.25, 2);

        $lateDeduction      = round($totalLateMinutes * ($hourlyRate / 60), 2);
        $undertimeDeduction = round($totalUndertimeMinutes * ($hourlyRate / 60), 2);

        $grossPay = round($basicPay + $overtimePay - $lateDeduction - $undertimeDeduction, 2);

        // Government deductions (semi-monthly = monthly / 2)
        $sss        = round($this->computeSSS($basicSalary) / 2, 2);
        $philhealth = round($this->computePhilHealth($basicSalary) / 2, 2);
        $pagibig    = round($this->computePagIBIG($basicSalary) / 2, 2);

        // Taxable income (semi-monthly)
        $taxableIncome = $grossPay - $sss - $philhealth - $pagibig;
        $tax           = $this->computeTax(max(0, $taxableIncome));

        // Other deductions
        $otherDeductionsAmount = (float) OtherDeduction::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->sum('amount_per_cutoff');

        $totalDeductions = round($sss + $philhealth + $pagibig + $tax + $otherDeductionsAmount, 2);
        $netPay          = round($grossPay - $totalDeductions, 2);

        return [
            'working_days'         => $workingDays,
            'days_present'         => $daysPresent,
            'days_absent'          => $daysAbsent,
            'basic_pay'            => $basicPay,
            'overtime_hours'       => $overtimeHours,
            'overtime_pay'         => $overtimePay,
            'late_minutes'         => $totalLateMinutes,
            'late_deduction'       => $lateDeduction,
            'undertime_minutes'    => $totalUndertimeMinutes,
            'undertime_deduction'  => $undertimeDeduction,
            'gross_pay'            => $grossPay,
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
     * Count approved leave days within a date range (excludes Sundays).
     */
    protected function getApprovedLeaveDays(int $employeeId, $startDate, $endDate): float
    {
        $leaves = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
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
            'basic_pay' => 0, 'overtime_hours' => 0, 'overtime_pay' => 0,
            'late_minutes' => 0, 'late_deduction' => 0,
            'undertime_minutes' => 0, 'undertime_deduction' => 0,
            'gross_pay' => 0, 'sss_deduction' => 0, 'philhealth_deduction' => 0,
            'pagibig_deduction' => 0, 'tax_deduction' => 0, 'other_deductions' => 0,
            'total_deductions' => 0, 'net_pay' => 0,
        ];
    }
}
