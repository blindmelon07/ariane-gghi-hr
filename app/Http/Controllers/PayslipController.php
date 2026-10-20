<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\PhilippinePayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /**
     * Roles cleared to see any employee's payroll, matching the middleware on the
     * admin payroll routes. Everyone else — including head_nurse, security_guard,
     * manager, department_head and approver — gets their own payslips only.
     */
    private const PAYROLL_ROLES = ['hr_admin', 'super_admin'];

    public function download(Request $request, Payslip $payslip)
    {
        $user = $request->user();

        // Anyone without company-wide payroll access may only fetch their own
        // payslips. Checking for the 'employee' role alone let every other
        // non-payroll role download any payslip by guessing its id.
        if (! in_array($user->role, self::PAYROLL_ROLES, true)) {
            $employee = Employee::where('emp_code', $user->employee_code)->first();

            if (! $employee || $payslip->employee_id !== $employee->id) {
                abort(403);
            }
        }

        $payslip->load(['employee', 'payrollPeriod']);

        $pdf = Pdf::loadView('pdf.payslip', compact('payslip'))
            ->setPaper('A4', 'portrait');

        $filename = 'payslip-' . $payslip->employee->emp_code . '-' . $payslip->payrollPeriod->start_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function adminDownload(Employee $employee, PayrollPeriod $period, PhilippinePayrollService $payrollService)
    {
        $data    = $payrollService->computePayslip($employee, $period);
        $payslip = Payslip::updateOrCreate(
            ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
            $data,
        );
        $payslip->load(['employee', 'payrollPeriod']);

        $pdf = Pdf::loadView('pdf.payslip', compact('payslip'))
            ->setPaper('A4', 'portrait');

        $filename = 'payslip-' . $employee->emp_code . '-' . $period->start_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
