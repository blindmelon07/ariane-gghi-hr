<?php

namespace App\Http\Controllers;

use App\Models\CommodityAllowanceForm;

class CommodityAllowanceFormController extends Controller
{
    /** HR may open any form; employees only their own. */
    private const VIEW_ANY_ROLES = ['hr_admin', 'super_admin'];

    public function show(CommodityAllowanceForm $commodityForm)
    {
        $user    = auth()->user();
        $isOwner = $user->employee_code && $commodityForm->employee?->emp_code === $user->employee_code;
        abort_unless($isOwner || \in_array($user->role, self::VIEW_ANY_ROLES, true), 403);

        return view('commodity-form-show', ['form' => $commodityForm]);
    }
}
