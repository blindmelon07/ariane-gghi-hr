<?php

namespace App\Http\Controllers;

use App\Models\PatientTransport;

class PatientTransportController extends Controller
{
    /** Roles that may open any slip; filers may only open their own. */
    private const VIEW_ANY_ROLES = ['hr_admin', 'super_admin', 'security_guard'];

    public function show(PatientTransport $patientTransport)
    {
        $user = auth()->user();
        $patientTransport->load('employee', 'approver', 'departedBy', 'arrivedBy');

        $isOwner = $user->employee_code && $patientTransport->employee->emp_code === $user->employee_code;
        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);

        return view('patient-transport-show', ['slip' => $patientTransport]);
    }
}
