<?php

namespace App\Http\Controllers;

use App\Models\SalaryDeductionRequest;
use Illuminate\Support\Facades\Storage;

class SalaryDeductionController extends Controller
{
    /** HR handles deductions; employees may only open their own forms. */
    private const VIEW_ANY_ROLES = ['hr_admin', 'super_admin'];

    public function show(SalaryDeductionRequest $salaryDeduction)
    {
        $this->authorizeView($salaryDeduction);
        $salaryDeduction->load('employee', 'lines.deduction', 'approver');

        return view('salary-deduction-show', ['form' => $salaryDeduction]);
    }

    public function attachment(SalaryDeductionRequest $salaryDeduction)
    {
        $this->authorizeView($salaryDeduction);
        abort_unless($salaryDeduction->attachment_path && Storage::disk('local')->exists($salaryDeduction->attachment_path), 404);

        return Storage::disk('local')->response($salaryDeduction->attachment_path);
    }

    private function authorizeView(SalaryDeductionRequest $form): void
    {
        $user    = auth()->user();
        $isOwner = $user->employee_code && $form->employee?->emp_code === $user->employee_code;

        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);
    }
}
