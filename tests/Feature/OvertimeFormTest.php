<?php

use App\Livewire\Admin\OvertimeFormApprovals;
use App\Livewire\Employee\OvertimeRequestForm;
use App\Models\Employee;
use App\Models\OvertimeForm;
use App\Models\OvertimeRequest;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 09:00:00', 'Asia/Manila'));
});

function otUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function otEmployee(string $code): Employee
{
    return Employee::create(['emp_code' => $code, 'first_name' => 'Over', 'last_name' => $code, 'is_active' => true]);
}

function otFile(User $user, array $lines, string $purpose = 'Year-end inventory count')
{
    return Livewire::actingAs($user)->test(OvertimeRequestForm::class)
        ->set('lines', $lines)
        ->set('purpose', $purpose)
        ->call('submit');
}

function otApprove(User $approver, OvertimeForm $form): void
{
    Livewire::actingAs($approver)->test(OvertimeFormApprovals::class)
        ->call('openAction', $form->id, 'approve')
        ->call('confirmAction');
}

it('computes hours per date, including past midnight', function () {
    expect(OvertimeRequestForm::hoursBetween('17:00', '20:30'))->toBe(3.5)
        ->and(OvertimeRequestForm::hoursBetween('22:00', '02:00'))->toBe(4.0)
        ->and(OvertimeRequestForm::hoursBetween('', '02:00'))->toEqual(0);
});

it('files one form with a line per date', function () {
    $user = otUser('employee', 'OT1');
    $emp  = otEmployee('OT1');

    otFile($user, [
        ['date' => '2026-10-23', 'from' => '17:00', 'to' => '20:00'],
        ['date' => '2026-10-24', 'from' => '17:00', 'to' => '19:30'],
    ])->assertHasNoErrors();

    $form = OvertimeForm::with('lines')->sole();
    expect($form->employee_id)->toBe($emp->id)
        ->and($form->approval_step)->toBe(1)
        ->and($form->lines)->toHaveCount(2)
        ->and($form->totalHours())->toBe(5.5)
        ->and($form->schedule_from)->toStartWith('08:00')
        ->and($form->lines->first()->status)->toBe('pending');
});

it('rejects dates in different months, repeated dates and already-requested dates', function () {
    $user = otUser('employee', 'OT2');
    otEmployee('OT2');

    otFile($user, [
        ['date' => '2026-10-30', 'from' => '17:00', 'to' => '19:00'],
        ['date' => '2026-11-02', 'from' => '17:00', 'to' => '19:00'],
    ])->assertHasErrors('general');

    otFile($user, [
        ['date' => '2026-10-23', 'from' => '17:00', 'to' => '19:00'],
        ['date' => '2026-10-23', 'from' => '20:00', 'to' => '21:00'],
    ])->assertHasErrors('lines.0.date');

    otFile($user, [['date' => '2026-10-23', 'from' => '17:00', 'to' => '19:00']])->assertHasNoErrors();
    otFile($user, [['date' => '2026-10-23', 'from' => '19:00', 'to' => '20:00']])->assertHasErrors('lines.0.date');
});

it('goes Dept Head → HR → CEO/MD, recording each sign-off, and only then counts for payroll', function () {
    $user = otUser('employee', 'OT3');
    otEmployee('OT3');
    otFile($user, [['date' => '2026-10-23', 'from' => '17:00', 'to' => '20:00']]);
    $form = OvertimeForm::sole();

    $head = otUser('department_head');
    $hr   = otUser('hr_admin');
    $ceo  = otUser('approver');

    otApprove($head, $form);
    expect($form->fresh()->approval_step)->toBe(2)
        ->and($form->fresh()->head_approved_by)->toBe($head->id);

    otApprove($hr, $form);
    expect($form->fresh()->approval_step)->toBe(3)
        ->and($form->fresh()->hr_verified_by)->toBe($hr->id)
        ->and(OvertimeRequest::where('status', 'approved')->count())->toBe(0);

    otApprove($ceo, $form);
    $line = OvertimeRequest::sole();
    expect($form->fresh()->status)->toBe('approved')
        ->and($form->fresh()->approved_by)->toBe($ceo->id)
        ->and($line->status)->toBe('approved')
        ->and($line->effectiveHours())->toBe(3.0);
});

it('rejecting the form rejects every date on it', function () {
    $user = otUser('employee', 'OT4');
    otEmployee('OT4');
    otFile($user, [
        ['date' => '2026-10-23', 'from' => '17:00', 'to' => '20:00'],
        ['date' => '2026-10-24', 'from' => '17:00', 'to' => '20:00'],
    ]);
    $form = OvertimeForm::sole();

    Livewire::actingAs(otUser('department_head'))->test(OvertimeFormApprovals::class)
        ->call('openAction', $form->id, 'reject')
        ->set('remarks', 'Not needed this week')
        ->call('confirmAction');

    expect(OvertimeRequest::pluck('status')->unique()->all())->toBe(['rejected']);
});

it('shows the printable form to the employee and approvers only', function () {
    $user = otUser('employee', 'OT5');
    otEmployee('OT5');
    otFile($user, [['date' => '2026-10-23', 'from' => '17:00', 'to' => '20:00']]);
    $form = OvertimeForm::sole();

    $this->actingAs($user)->get("/overtime/form/{$form->id}")->assertOk()->assertSee('OVERTIME REQUEST FORM')->assertSee('3.00');
    $this->actingAs(otUser('department_head'))->get("/overtime/form/{$form->id}")->assertOk();
    $this->actingAs(otUser('employee', 'OT6'))->get("/overtime/form/{$form->id}")->assertForbidden();
    $this->actingAs(otUser('department_head'))->get('/admin/overtime')->assertOk();
});
