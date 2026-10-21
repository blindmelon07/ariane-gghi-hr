<?php

namespace App\Http\Controllers;

use App\Models\OvertimeForm;

class OvertimeFormController extends Controller
{
    /** Roles in the approval chain may open any form; employees only their own. */
    private const VIEW_ANY_ROLES = ['manager', 'department_head', 'hr_admin', 'approver', 'super_admin'];

    public function show(OvertimeForm $overtimeForm)
    {
        $user = auth()->user();
        $overtimeForm->load('employee', 'lines', 'headApprover', 'hrVerifier', 'approver');

        $isOwner = $user->employee_code && $overtimeForm->employee->emp_code === $user->employee_code;
        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);

        return view('overtime-form-show', ['form' => $overtimeForm]);
    }
}
