<?php

namespace App\Http\Controllers;

use App\Models\GatePass;

class GatePassController extends Controller
{
    /** Roles that may open anyone's pass; employees may only open their own. */
    private const VIEW_ANY_ROLES = ['hr_admin', 'super_admin', 'security_guard'];

    public function show(GatePass $gatePass)
    {
        $user = auth()->user();
        $gatePass->load('employee', 'approver');

        $isOwner = $user->employee_code && $gatePass->employee->emp_code === $user->employee_code;
        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);

        return view('gate-pass-show', ['pass' => $gatePass]);
    }
}
