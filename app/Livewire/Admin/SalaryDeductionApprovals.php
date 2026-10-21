<?php

namespace App\Livewire\Admin;

use App\Models\SalaryDeductionRequest;
use App\Services\SalaryDeductionService;
use Illuminate\Database\Eloquent\Model;

/**
 * HR approves Salary Deduction Forms; approval creates the payroll
 * deductions automatically.
 */
class SalaryDeductionApprovals extends StepApprovals
{
    public const STEPS = [
        1 => ['role' => 'hr_admin', 'label' => 'HR'],
    ];

    protected function model(): string { return SalaryDeductionRequest::class; }
    protected function label(): string { return 'salary deduction request'; }
    protected function dateColumn(): string { return 'created_at'; }
    protected function employeeRoute(): string { return 'salary-deduction.request'; }
    protected function steps(): array { return self::STEPS; }
    protected function relations(): array { return ['employee', 'lines']; }
    public function viewRoute(): ?string { return 'salary-deduction.show'; }

    protected function describe(Model $request): string
    {
        return 'salary deduction request (₱' . number_format($request->totalPerCutoff(), 2) . ' per cut-off)';
    }

    public function details(Model $request): array
    {
        return [
            'Date of Request' => $request->created_at->format('M d, Y'),
            'Particulars'     => $request->lines->map(fn ($l) => $l->label() . ' ₱' . number_format((float) $l->amount_per_cutoff, 2))->join('; '),
        ];
    }

    protected function afterFinalApproval(Model $request): void
    {
        app(SalaryDeductionService::class)->apply($request);
    }
}
