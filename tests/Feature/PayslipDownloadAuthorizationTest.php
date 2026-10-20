<?php

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Spatie\Permission\Models\Role;

/*
 * payslips.download runs on bare 'auth' middleware, so the controller is the only
 * thing standing between a signed-in user and any payslip id. It used to scope
 * ownership with `$user->role === 'employee'`, which meant every other non-payroll
 * role (security_guard, head_nurse, manager, department_head, approver) could
 * download anyone's payslip by guessing the id.
 */

function payslipUser(string $role, ?string $empCode = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

    $user = User::factory()->create(array_filter([
        'role'          => $role,
        'employee_code' => $empCode,
    ]));

    $user->syncRoles($role);

    return $user;
}

function payslipFor(string $empCode = 'PAY001'): Payslip
{
    $employee = Employee::create([
        'emp_code'        => $empCode,
        'first_name'      => 'Payroll',
        'last_name'       => 'Subject',
        'is_active'       => true,
        'employment_type' => 'regular',
    ]);

    $period = PayrollPeriod::create([
        'name'                   => 'Oct 1-15 2026 ('.$empCode.')',
        'cutoff_type'            => '1st',
        'custom_deduction_scope' => 'both',
        'start_date'             => '2026-10-01',
        'end_date'               => '2026-10-15',
        'status'                 => 'processed',
    ]);

    return Payslip::create([
        'employee_id'        => $employee->id,
        'payroll_period_id'  => $period->id,
        'working_days'       => 11,
        'days_present'       => 11,
        'days_absent'        => 0,
        'basic_pay'          => 20000,
        'gross_pay'          => 20000,
        'total_deductions'   => 1000,
        'net_pay'            => 19000,
        'status'             => 'released',
    ]);
}

it('blocks a security guard from downloading someone elses payslip', function () {
    $payslip = payslipFor();

    $this->actingAs(payslipUser('security_guard', 'GUARD-X'))
        ->get(route('payslips.download', $payslip))
        ->assertForbidden();
});

it('blocks other non-payroll roles from downloading someone elses payslip', function (string $role) {
    $payslip = payslipFor('PAY-'.$role);

    $this->actingAs(payslipUser($role, 'CODE-'.$role))
        ->get(route('payslips.download', $payslip))
        ->assertForbidden();
})->with(['employee', 'head_nurse', 'manager', 'department_head', 'approver']);

it('lets an employee download their own payslip', function () {
    $payslip  = payslipFor('OWN001');
    $owner    = payslipUser('employee', 'OWN001');

    $response = $this->actingAs($owner)->get(route('payslips.download', $payslip));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('lets payroll roles download any payslip', function (string $role) {
    $payslip = payslipFor('ANY-'.$role);

    $this->actingAs(payslipUser($role, 'ADM-'.$role))
        ->get(route('payslips.download', $payslip))
        ->assertOk();
})->with(['hr_admin', 'super_admin']);

it('blocks a user whose employee record is missing entirely', function () {
    $payslip = payslipFor('GHOST1');

    // employee_code points at no Employee row, so ownership can never be proven.
    $this->actingAs(payslipUser('employee', 'NO-SUCH-CODE'))
        ->get(route('payslips.download', $payslip))
        ->assertForbidden();
});
