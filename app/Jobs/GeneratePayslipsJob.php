<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\PhilippinePayrollService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePayslipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $payrollPeriodId,
    ) {}

    public function handle(PhilippinePayrollService $payroll): void
    {
        $period = PayrollPeriod::findOrFail($this->payrollPeriodId);

        $employees = Employee::where('is_active', true)
            ->whereHas('salaryDetail')
            ->get();

        foreach ($employees as $employee) {
            $data = $payroll->computePayslip($employee, $period);

            Payslip::updateOrCreate(
                [
                    'employee_id'       => $employee->id,
                    'payroll_period_id' => $period->id,
                ],
                $data,
            );
        }

        $period->update(['status' => 'processed']);
    }
}
