<?php

namespace App\Http\Controllers;

use App\Models\CoeRequest;

class CoeRequestController extends Controller
{
    /** HR may open any request; employees only their own. */
    private const VIEW_ANY_ROLES = ['hr_admin', 'super_admin'];

    public function show(CoeRequest $coeRequest)
    {
        $user = auth()->user();
        $coeRequest->load('employee', 'releaser');

        $isOwner = $user->employee_code && $coeRequest->employee?->emp_code === $user->employee_code;
        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);

        return view('coe-request-show', ['coe' => $coeRequest]);
    }
}
