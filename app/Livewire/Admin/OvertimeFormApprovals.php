<?php

namespace App\Livewire\Admin;

use App\Models\OvertimeForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Overtime Request Form approvals: Dept Head (recommends) → HR (verifies)
 * → CEO / Medical Director (approves) — the leave approval chain.
 */
class OvertimeFormApprovals extends StepApprovals
{
    protected function model(): string { return OvertimeForm::class; }
    protected function label(): string { return 'overtime request'; }
    protected function dateColumn(): string { return 'created_at'; }
    protected function employeeRoute(): string { return 'overtime.request'; }
    protected function relations(): array { return ['employee', 'lines']; }
    public function viewRoute(): ?string { return 'overtime.show'; }

    protected function describe(Model $request): string
    {
        return "overtime request ({$request->monthLabel()}, {$request->totalHours()} hrs)";
    }

    public function details(Model $request): array
    {
        $dates = $request->lines->map(fn ($l) => $l->date->format('M d'))->join(', ');

        return [
            'Month'       => $request->monthLabel(),
            'Date/s'      => $dates ?: '—',
            'Total Hours' => number_format($request->totalHours(), 2),
        ];
    }

    protected function recordStepApproval(Model $request, int $step, User $user): void
    {
        match ($step) {
            1       => $request->update(['head_approved_by' => $user->id, 'head_approved_at' => now()]),
            2       => $request->update(['hr_verified_by' => $user->id, 'hr_verified_at' => now()]),
            default => null,
        };
    }
}
