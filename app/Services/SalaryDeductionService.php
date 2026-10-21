<?php

namespace App\Services;

use App\Models\DeductionType;
use App\Models\OtherDeductionPayment;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryDeductionRequest;
use Illuminate\Support\Facades\DB;

class SalaryDeductionService
{
    /**
     * Turn each line of an approved request into an active payroll deduction.
     * Lines with a total to pay are auto-stop: drawn down on each finalized
     * payroll and switched off when paid.
     */
    public function apply(SalaryDeductionRequest $request): void
    {
        DB::transaction(function () use ($request) {
            foreach ($request->lines()->whereNull('other_deduction_id')->get() as $line) {
                $code = SalaryDeductionRequest::PARTICULARS[$line->particular][1] ?? 'OTHERS';
                $hasTotal = $line->total_amount !== null;

                $deduction = $request->employee->otherDeductions()->create([
                    'deduction_type_id' => DeductionType::where('code', $code)->value('id'),
                    'description'       => $line->label() . " (Salary Deduction Form #{$request->id})",
                    'amount_per_cutoff' => $line->amount_per_cutoff,
                    'cutoff_schedule'   => $line->cutoff_schedule,
                    'starts_on'         => $line->starts_on,
                    'remaining_balance' => $hasTotal ? $line->total_amount : null,
                    'auto_stop'         => $hasTotal,
                    'is_active'         => true,
                ]);

                $line->update(['other_deduction_id' => $deduction->id]);
            }
        });
    }

    /**
     * Draw down auto-stop deductions for a finalized period: record what the
     * period took, lower the balance, and switch the deduction off at zero.
     * A period is only ever recorded once per deduction.
     *
     * @return int number of deductions drawn down
     */
    public function recordPeriod(PayrollPeriod $period): int
    {
        $payroll = app(PhilippinePayrollService::class);
        $count   = 0;

        $payslips = Payslip::with('employee')->where('payroll_period_id', $period->id)->get();

        DB::transaction(function () use ($payslips, $period, $payroll, &$count) {
            foreach ($payslips as $payslip) {
                if (! $payslip->employee) continue;

                foreach ($payroll->applicableDeductions($payslip->employee, $period) as $deduction) {
                    if (! $deduction->auto_stop) continue;

                    $amount = $deduction->amountThisCutoff();
                    $already = OtherDeductionPayment::where('other_deduction_id', $deduction->id)
                        ->where('payroll_period_id', $period->id)
                        ->exists();
                    if ($amount <= 0 || $already) continue;

                    $balance = round(max(0, (float) $deduction->remaining_balance - $amount), 2);

                    OtherDeductionPayment::create([
                        'other_deduction_id' => $deduction->id,
                        'payroll_period_id'  => $period->id,
                        'amount'             => $amount,
                        'balance_after'      => $balance,
                    ]);

                    $deduction->update([
                        'remaining_balance' => $balance,
                        'is_active'         => $balance > 0,
                    ]);

                    $count++;
                }
            }
        });

        return $count;
    }
}
