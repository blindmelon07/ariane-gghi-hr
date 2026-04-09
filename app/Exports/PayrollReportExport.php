<?php

namespace App\Exports;

use App\Models\Payslip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected int $periodId,
    ) {}

    public function collection()
    {
        return Payslip::with('employee')
            ->where('payroll_period_id', $this->periodId)
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Emp Code',
            'Name',
            'Days Present',
            'Basic Pay',
            'OT Pay',
            'Gross Pay',
            'SSS',
            'PhilHealth',
            'Pag-IBIG',
            'Tax',
            'Others',
            'Total Deductions',
            'Net Pay',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code ?? '',
            $row->employee->full_name ?? '',
            number_format($row->days_present, 1),
            number_format($row->basic_pay, 2),
            number_format($row->overtime_pay, 2),
            number_format($row->gross_pay, 2),
            number_format($row->sss_deduction, 2),
            number_format($row->philhealth_deduction, 2),
            number_format($row->pagibig_deduction, 2),
            number_format($row->tax_deduction, 2),
            number_format($row->other_deductions, 2),
            number_format($row->total_deductions, 2),
            number_format($row->net_pay, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
