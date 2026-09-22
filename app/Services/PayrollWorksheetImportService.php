<?php

namespace App\Services;

use App\Models\DeductionType;
use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryDetail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Imports the "WORKSHEET" sheet of a GSAC-style payroll Excel file
 * (see e.g. "09 Sept 10, 2026 - PAs.xlsx") into salary_details + payslips
 * for a given payroll period. Figures are taken as-is from the sheet
 * rather than recomputed, since the source file is the trusted payroll run.
 */
class PayrollWorksheetImportService
{
    private const SHEET_NAME = 'WORKSHEET';

    private const FIRST_DATA_ROW = 7;

    // 1-indexed column positions on the WORKSHEET sheet.
    private const COL_SURNAME = 2;

    private const COL_FIRST_NAME = 3;

    private const COL_DAILY_RATE = 8;

    private const COL_LATE_ABSENCES = 12;

    private const COL_OT_HOURS = 13;

    private const COL_BASIC_SALARY = 18;

    private const COL_HAZARD_PAY = 19;

    private const COL_MEDICAL_ALLOWANCE = 20;

    private const COL_OTHER_ALLOWANCE = 21;

    private const COL_GROSS_PAY = 23;

    private const COL_CHARGE_SLIPS = 24;

    private const COL_OTHERS = 25;

    private const COL_SALARY_LOAN = 26;

    private const COL_LOANS = 28;

    private const COL_SAVINGS = 29;

    private const COL_MEMBERSHIP = 30;

    private const COL_SSS_CONT = 32;

    private const COL_SSS_LOAN = 33;

    private const COL_PHIC = 34;

    private const COL_HDMF = 35;

    private const COL_HDMF_LOAN = 36;

    private const COL_AP_OTHERS = 39;

    private const COL_WHT = 41;

    private const COL_NET_PAY_AFTER_TAX = 42;

    private const COL_RICE_ALLOWANCE = 48;

    private const COL_COMMODITY_ALLOWANCE = 49;

    /**
     * @param  array<int, string>|null  $onlyEmpCodes  If given, only these emp_codes are written — every other
     *                                                  matched row is skipped (used to refresh a subset without
     *                                                  clobbering employees already updated by another source,
     *                                                  e.g. the system's own attendance-based generation).
     * @return array{matched: int, unmatched: array<int, string>}
     */
    public function import(string $filePath, PayrollPeriod $period, ?array $onlyEmpCodes = null): array
    {
        $sheet = $this->loadSheet($filePath);

        $matched   = 0;
        $unmatched = [];

        $row = self::FIRST_DATA_ROW;
        while (true) {
            $surname = trim((string) $this->cellRaw($sheet, self::COL_SURNAME, $row));
            if ($surname === '') {
                break;
            }

            $firstName = trim((string) $this->cellRaw($sheet, self::COL_FIRST_NAME, $row));
            $employee  = $this->matchEmployee($surname, $firstName);

            if (! $employee) {
                $unmatched[] = trim("{$surname}, {$firstName}");
                $row++;
                continue;
            }

            if ($onlyEmpCodes !== null && ! in_array($employee->emp_code, $onlyEmpCodes, true)) {
                $row++;
                continue;
            }

            $this->upsertSalaryDetail($employee, $sheet, $row);
            $this->upsertPayslip($employee, $period, $sheet, $row);
            $this->seedStatutoryDeductions($employee, $sheet, $row);

            $matched++;
            $row++;
        }

        return [
            'matched'   => $matched,
            'unmatched' => $unmatched,
        ];
    }

    private function loadSheet(string $filePath): Worksheet
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getSheetByName(self::SHEET_NAME);

        if (! $sheet) {
            throw new RuntimeException('Sheet "'.self::SHEET_NAME.'" was not found in the uploaded file.');
        }

        return $sheet;
    }

    private function matchEmployee(string $surname, string $firstName): ?Employee
    {
        $candidates = Employee::whereRaw('LOWER(TRIM(last_name)) = ?', [Str::lower($surname)])->get();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->count() > 1) {
            $needle = Str::lower(Str::before($firstName, ' '));

            return $candidates->first(
                fn (Employee $e) => $needle !== '' && Str::contains(Str::lower($e->first_name), $needle)
            );
        }

        return null;
    }

    /**
     * Reads a cell's last-saved value rather than recomputing its formula —
     * PhpSpreadsheet's own calculation engine mis-evaluates some formulas in
     * this workbook (e.g. withholding tax and the employee-name VLOOKUPs),
     * while Excel's last-saved result, cached in the file, is correct.
     */
    private function cellRaw(Worksheet $sheet, int $col, int $row): mixed
    {
        $cell = $sheet->getCell([$col, $row]);

        return $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
    }

    private function cellValue(Worksheet $sheet, int $col, int $row): float
    {
        return (float) ($this->cellRaw($sheet, $col, $row) ?? 0);
    }

    private function upsertSalaryDetail(Employee $employee, Worksheet $sheet, int $row): void
    {
        SalaryDetail::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'rate_type'           => 'daily',
                'daily_rate'          => $this->cellValue($sheet, self::COL_DAILY_RATE, $row),
                'hourly_rate'         => round($this->cellValue($sheet, self::COL_DAILY_RATE, $row) / 8, 2),
                'basic_salary'        => $this->cellValue($sheet, self::COL_BASIC_SALARY, $row),
                'hazard_pay'          => $this->cellValue($sheet, self::COL_HAZARD_PAY, $row),
                'medical_allowance'   => $this->cellValue($sheet, self::COL_MEDICAL_ALLOWANCE, $row),
                'other_allowance'     => $this->cellValue($sheet, self::COL_OTHER_ALLOWANCE, $row),
                'rice_allowance'      => $this->cellValue($sheet, self::COL_RICE_ALLOWANCE, $row),
                'commodity_allowance' => $this->cellValue($sheet, self::COL_COMMODITY_ALLOWANCE, $row),
                'effective_date'      => now()->toDateString(),
                'is_active'           => true,
            ]
        );
    }

    private function upsertPayslip(Employee $employee, PayrollPeriod $period, Worksheet $sheet, int $row): void
    {
        $hazardPay          = $this->cellValue($sheet, self::COL_HAZARD_PAY, $row);
        $medicalAllowance    = $this->cellValue($sheet, self::COL_MEDICAL_ALLOWANCE, $row);
        $otherAllowance      = $this->cellValue($sheet, self::COL_OTHER_ALLOWANCE, $row);
        $riceAllowance       = $this->cellValue($sheet, self::COL_RICE_ALLOWANCE, $row);
        $commodityAllowance  = $this->cellValue($sheet, self::COL_COMMODITY_ALLOWANCE, $row);

        $sssDeduction        = $this->cellValue($sheet, self::COL_SSS_CONT, $row);
        $philhealthDeduction = $this->cellValue($sheet, self::COL_PHIC, $row);
        $pagibigDeduction    = $this->cellValue($sheet, self::COL_HDMF, $row);
        $taxDeduction        = $this->cellValue($sheet, self::COL_WHT, $row);

        $otherDeductions = $this->cellValue($sheet, self::COL_CHARGE_SLIPS, $row)
            + $this->cellValue($sheet, self::COL_OTHERS, $row)
            + $this->cellValue($sheet, self::COL_SALARY_LOAN, $row)
            + $this->cellValue($sheet, self::COL_LOANS, $row)
            + $this->cellValue($sheet, self::COL_SAVINGS, $row)
            + $this->cellValue($sheet, self::COL_MEMBERSHIP, $row)
            + $this->cellValue($sheet, self::COL_SSS_LOAN, $row)
            + $this->cellValue($sheet, self::COL_HDMF_LOAN, $row)
            + $this->cellValue($sheet, self::COL_AP_OTHERS, $row);

        $totalDeductions = $sssDeduction + $philhealthDeduction + $pagibigDeduction + $taxDeduction + $otherDeductions;

        Payslip::updateOrCreate(
            ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
            [
                'overtime_hours'       => $this->cellValue($sheet, self::COL_OT_HOURS, $row),
                'overtime_pay'         => 0,
                'late_deduction'       => $this->cellValue($sheet, self::COL_LATE_ABSENCES, $row),
                'basic_pay'            => $this->cellValue($sheet, self::COL_BASIC_SALARY, $row),
                'gross_pay'            => $this->cellValue($sheet, self::COL_GROSS_PAY, $row),
                'hazard_pay'           => $hazardPay,
                'medical_allowance'    => $medicalAllowance,
                'other_allowance'      => $otherAllowance,
                'rice_allowance'       => $riceAllowance,
                'commodity_allowance'  => $commodityAllowance,
                'total_allowances'     => $hazardPay + $medicalAllowance + $otherAllowance + $riceAllowance + $commodityAllowance,
                'sss_deduction'        => $sssDeduction,
                'philhealth_deduction' => $philhealthDeduction,
                'pagibig_deduction'    => $pagibigDeduction,
                'tax_deduction'        => $taxDeduction,
                'other_deductions'     => $otherDeductions,
                'total_deductions'     => $totalDeductions,
                'net_pay'              => $this->cellValue($sheet, self::COL_NET_PAY_AFTER_TAX, $row),
            ]
        );
    }

    /**
     * Mirrors the sheet's government contributions and loan figures into
     * other_deductions, using the same deduction-type codes that
     * PhilippinePayrollService::computePayslip() groups by (SSS, PHC, PAG…, WHT…)
     * — so the system's own "Generate" can reproduce this payslip on its own
     * for future cutoffs, not just this one-off import.
     */
    private function seedStatutoryDeductions(Employee $employee, Worksheet $sheet, int $row): void
    {
        $this->seedDeduction($employee, 'SSS', 'SSS Contribution', 'government', $this->cellValue($sheet, self::COL_SSS_CONT, $row));
        $this->seedDeduction($employee, 'PHC', 'PhilHealth Contribution', 'government', $this->cellValue($sheet, self::COL_PHIC, $row));
        $this->seedDeduction($employee, 'PAG_IBIG', 'Pag-IBIG Contribution', 'government', $this->cellValue($sheet, self::COL_HDMF, $row));
        $this->seedDeduction($employee, 'WHT', 'Withholding Tax', 'government', $this->cellValue($sheet, self::COL_WHT, $row));
        $this->seedDeduction($employee, 'SSS_LOAN', 'SSS Salary Loan', 'loan', $this->cellValue($sheet, self::COL_SSS_LOAN, $row));
        $this->seedDeduction($employee, 'PAGIBIG_LOAN', 'Pag-IBIG Fund Loan', 'loan', $this->cellValue($sheet, self::COL_HDMF_LOAN, $row));

        $loanBucket = $this->cellValue($sheet, self::COL_CHARGE_SLIPS, $row)
            + $this->cellValue($sheet, self::COL_OTHERS, $row)
            + $this->cellValue($sheet, self::COL_SALARY_LOAN, $row)
            + $this->cellValue($sheet, self::COL_LOANS, $row)
            + $this->cellValue($sheet, self::COL_SAVINGS, $row)
            + $this->cellValue($sheet, self::COL_MEMBERSHIP, $row)
            + $this->cellValue($sheet, self::COL_AP_OTHERS, $row);

        $this->seedDeduction($employee, 'CASH_ADVANCE', 'Salary Loan / Other Deduction', 'loan', $loanBucket);
    }

    private function seedDeduction(Employee $employee, string $code, string $name, string $category, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $type = DeductionType::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'category' => $category, 'is_active' => true]
        );

        OtherDeduction::updateOrCreate(
            ['employee_id' => $employee->id, 'deduction_type_id' => $type->id],
            [
                'description'        => $name.' (imported from payroll worksheet)',
                'amount_per_cutoff'  => $amount,
                'cutoff_schedule'    => 'both',
                'is_active'          => true,
            ]
        );
    }
}
