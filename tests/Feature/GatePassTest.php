<?php

use App\Livewire\Admin\GatePassApprovals;
use App\Livewire\Employee\GatePassForm;
use App\Models\Employee;
use App\Models\GatePass;
use App\Models\User;
use App\Notifications\HrAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 09:00:00', 'Asia/Manila'));
});

function gpUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function gpEmployee(string $code): Employee
{
    return Employee::create(['emp_code' => $code, 'first_name' => 'Gate', 'last_name' => $code, 'is_active' => true]);
}

function gpPass(Employee $emp, string $status = 'pending'): GatePass
{
    return GatePass::create([
        'employee_id' => $emp->id, 'date' => '2026-10-22', 'time_out' => '10:00', 'time_of_arrival' => '11:30',
        'destination' => 'Bank', 'reason' => 'Office deposit', 'status' => $status, 'approval_step' => $status === 'pending' ? 1 : null,
    ])->fresh();
}

it('lets any employee file a gate pass, numbers it and notifies HR', function () {
    Notification::fake();
    $hr   = gpUser('hr_admin');
    $user = gpUser('employee', 'GP1');
    $emp  = gpEmployee('GP1');

    Livewire::actingAs($user)->test(GatePassForm::class)
        ->set('timeOut', '10:00')
        ->set('timeOfArrival', '11:30')
        ->set('destination', 'BDO Sorsogon')
        ->set('reason', 'Deposit for the cashier')
        ->call('submit')
        ->assertHasNoErrors();

    $pass = GatePass::where('employee_id', $emp->id)->sole();
    expect($pass->series_no)->toBe(sprintf('GP-2026-%05d', $pass->id))
        ->and($pass->status)->toBe('pending');

    Notification::assertSentTo($hr, HrAlert::class);
});

it('requires the time of arrival to be after the time out', function () {
    $user = gpUser('employee', 'GP2');
    gpEmployee('GP2');

    Livewire::actingAs($user)->test(GatePassForm::class)
        ->set('timeOut', '14:00')->set('timeOfArrival', '13:00')
        ->set('destination', 'Bank')->set('reason', 'Errand')
        ->call('submit')
        ->assertHasErrors('timeOfArrival');
});

it('is approved by HR in a single step', function () {
    $pass = gpPass(gpEmployee('GP3'));

    Livewire::actingAs(gpUser('hr_admin'))->test(GatePassApprovals::class)
        ->call('openAction', $pass->id, 'approve')
        ->call('confirmAction')
        ->assertHasNoErrors();

    expect($pass->fresh()->status)->toBe('approved')
        ->and($pass->fresh()->approved_by)->not->toBeNull();
});

it('cannot be approved by a department head', function () {
    $pass = gpPass(gpEmployee('GP4'));

    Livewire::actingAs(gpUser('department_head'))->test(GatePassApprovals::class)
        ->call('openAction', $pass->id, 'approve')
        ->call('confirmAction')
        ->assertHasErrors('remarks');

    expect($pass->fresh()->status)->toBe('pending');
    $this->actingAs(gpUser('department_head'))->get('/admin/gate-passes')->assertForbidden();
});

it('stops HR from approving their own gate pass', function () {
    $hr   = gpUser('hr_admin', 'GPHR');
    $pass = gpPass(gpEmployee('GPHR'));

    Livewire::actingAs($hr)->test(GatePassApprovals::class)
        ->call('openAction', $pass->id, 'approve')
        ->call('confirmAction')
        ->assertHasErrors('remarks');

    expect($pass->fresh()->status)->toBe('pending');
});

it('shows the pass to its owner, HR and guards, but not to other employees', function () {
    $owner = gpUser('employee', 'GP5');
    $pass  = gpPass(gpEmployee('GP5'), 'approved');

    $this->actingAs($owner)->get("/gate-pass/{$pass->id}")->assertOk()->assertSee('APPROVED')->assertSee($pass->series_no);
    $this->actingAs(gpUser('security_guard'))->get("/gate-pass/{$pass->id}")->assertOk();
    $this->actingAs(gpUser('hr_admin'))->get("/gate-pass/{$pass->id}")->assertOk();
    $this->actingAs(gpUser('employee', 'GP6'))->get("/gate-pass/{$pass->id}")->assertForbidden();
});

it('marks a pending pass as not yet valid', function () {
    $owner = gpUser('employee', 'GP7');
    $pass  = gpPass(gpEmployee('GP7'));

    $this->actingAs($owner)->get("/gate-pass/{$pass->id}")->assertOk()->assertSee('NOT YET VALID')->assertDontSee('>APPROVED<', false);
});

it('every role can open the filing page', function () {
    foreach (['employee', 'hr_admin', 'security_guard', 'approver', 'department_head'] as $role) {
        $this->actingAs(gpUser($role))->get('/gate-pass')->assertOk();
    }
});
