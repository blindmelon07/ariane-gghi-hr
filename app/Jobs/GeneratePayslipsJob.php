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

    public int $timeout = 300;

    /**
     * @param  int[]|null  $employeeIds  Only generate for these employees; null = everyone eligible.
     *                                   Payslips of employees left out are kept as they are.
     */
    public function __construct(
        public int $payrollPeriodId,
        public ?array $employeeIds = null,
    ) {}

    public function handle(PhilippinePayrollService $payroll): void
    {
        $period = PayrollPeriod::findOrFail($this->payrollPeriodId);

        $employees = PayrollPeriod::limitEmployeesToGroup(Employee::query(), $period->payroll_group)
            ->where('is_active', true)
            ->whereHas('salaryDetail')
            ->when($this->employeeIds !== null, fn ($q) => $q->whereIn('id', $this->employeeIds))
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
