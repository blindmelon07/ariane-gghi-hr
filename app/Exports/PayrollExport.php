<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected int $payrollPeriodId,
    ) {}

    public function collection()
    {
        return Payslip::with('employee')
            ->where('payroll_period_id', $this->payrollPeriodId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Department',
            'Position',
            'Working Days',
            'Days Present',
            'Days Absent',
            'Basic Pay',
            'Overtime Hours',
            'Overtime Pay',
            'Late (min)',
            'Late Deduction',
            'Undertime (min)',
            'Undertime Deduction',
            'Gross Pay',
            'SSS',
            'PhilHealth',
            'Pag-IBIG',
            'Tax',
            'Other Deductions',
            'Total Deductions',
            'Net Pay',
        ];
    }

    public function map($payslip): array
    {
        return [
            $payslip->employee->emp_code,
            $payslip->employee->full_name,
            $payslip->employee->department,
            $payslip->employee->position,
            $payslip->working_days,
            $payslip->days_present,
            $payslip->days_absent,
            $payslip->basic_pay,
            $payslip->overtime_hours,
            $payslip->overtime_pay,
            $payslip->late_minutes,
            $payslip->late_deduction,
            $payslip->undertime_minutes,
            $payslip->undertime_deduction,
            $payslip->gross_pay,
            $payslip->sss_deduction,
            $payslip->philhealth_deduction,
            $payslip->pagibig_deduction,
            $payslip->tax_deduction,
            $payslip->other_deductions,
            $payslip->total_deductions,
            $payslip->net_pay,
        ];
    }
}
