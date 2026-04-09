<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryDetail;
use Illuminate\Database\Seeder;

class FakePayrollSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where('is_active', true)->firstOrFail();

        // Ensure salary detail exists (₱18,000/month)
        SalaryDetail::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'rate_type'      => 'monthly',
                'basic_salary'   => 18000.00,
                'daily_rate'     => 692.31,   // 18000 / 26
                'hourly_rate'    => 86.54,    // daily / 8
                'effective_date' => '2025-01-01',
                'is_active'      => true,
            ]
        );

        // Add a sample other deduction (cash advance)
        OtherDeduction::updateOrCreate(
            ['employee_id' => $employee->id, 'description' => 'Cash Advance'],
            [
                'amount_per_cutoff'  => 500.00,
                'remaining_balance'  => 2000.00,
                'is_active'          => true,
            ]
        );

        // Create payroll period: April 1-15, 2026
        $period = PayrollPeriod::updateOrCreate(
            ['start_date' => '2026-04-01', 'end_date' => '2026-04-15'],
            [
                'name'        => 'April 1-15, 2026',
                'cutoff_type' => 'semi_monthly',
                'status'      => 'finalized',
                'processed_at' => now(),
            ]
        );

        // Compute payslip values
        $workingDays = 12;
        $daysPresent = 11;
        $daysAbsent  = 1;
        $dailyRate   = 692.31;

        $basicPay      = round($daysPresent * $dailyRate, 2);     // 7,615.41
        $overtimeHours = 4;
        $overtimePay   = round($overtimeHours * 86.54 * 1.25, 2); // 432.70
        $lateMinutes   = 15;
        $lateDeduction = round(($lateMinutes / 60) * 86.54, 2);   // 21.64
        $grossPay      = round($basicPay + $overtimePay - $lateDeduction, 2);

        // Philippine statutory deductions (based on ~₱18k monthly)
        $sss        = 810.00;   // employee share
        $philhealth = 225.00;   // 1.25% of 18k
        $pagibig    = 200.00;   // max ₱200
        $tax        = 0.00;     // below ₱20,833 threshold for semi-monthly
        $others     = 500.00;   // cash advance deduction

        $totalDeductions = round($sss + $philhealth + $pagibig + $tax + $others, 2);
        $netPay          = round($grossPay - $totalDeductions, 2);

        Payslip::updateOrCreate(
            ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
            [
                'working_days'         => $workingDays,
                'days_present'         => $daysPresent,
                'days_absent'          => $daysAbsent,
                'basic_pay'            => $basicPay,
                'overtime_hours'       => $overtimeHours,
                'overtime_pay'         => $overtimePay,
                'late_minutes'         => $lateMinutes,
                'late_deduction'       => $lateDeduction,
                'undertime_minutes'    => 0,
                'undertime_deduction'  => 0,
                'gross_pay'            => $grossPay,
                'sss_deduction'        => $sss,
                'philhealth_deduction' => $philhealth,
                'pagibig_deduction'    => $pagibig,
                'tax_deduction'        => $tax,
                'other_deductions'     => $others,
                'total_deductions'     => $totalDeductions,
                'net_pay'              => $netPay,
                'status'               => 'released',
            ]
        );

        $this->command->info("Payslip created for {$employee->full_name}: Gross ₱" . number_format($grossPay, 2) . " | Net ₱" . number_format($netPay, 2));
    }
}
