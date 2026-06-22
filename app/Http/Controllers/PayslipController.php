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
    public function download(Request $request, Payslip $payslip)
    {
        $user = $request->user();

        // Employees can only download their own payslips
        if ($user->role === 'employee') {
            $employee = Employee::where('emp_code', $user->employee_code)->first();

            if (!$employee || $payslip->employee_id !== $employee->id) {
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
