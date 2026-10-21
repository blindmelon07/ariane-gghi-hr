<?php

use App\Livewire\Admin\CoeRequests;
use App\Livewire\Employee\CoeRequestForm;
use App\Models\CoeRequest;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\HrAlert;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function coeUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function coeEmployee(string $code): Employee
{
    $emp = Employee::create(['emp_code' => $code, 'first_name' => 'Ana', 'last_name' => 'Reyes', 'is_active' => true, 'hire_date' => '2020-03-01']);
    $emp->forceFill(['department' => 'Nursing', 'position' => 'Staff Nurse'])->saveQuietly();
    return $emp;
}

function coeFile(User $user)
{
    return Livewire::actingAs($user)->test(CoeRequestForm::class)
        ->set('reason', 'Bank loan application')
        ->set('additionalDetails', 'Include monthly salary')
        ->call('submit');
}

it('prefills from the employee record and files a pending request, notifying HR', function () {
    Notification::fake();
    $hr   = coeUser('hr_admin');
    $user = coeUser('employee', 'COE1');
    $emp  = coeEmployee('COE1');

    Livewire::actingAs($user)->test(CoeRequestForm::class)
        ->assertSet('department', 'Nursing')
        ->assertSet('designation', 'Staff Nurse')
        ->assertSet('dateHired', '2020-03-01')
        ->assertSet('signatureName', $emp->full_name);

    coeFile($user)->assertHasNoErrors();

    $coe = CoeRequest::sole();
    expect($coe->status)->toBe('pending')->and($coe->additional_details)->toBe('Include monthly salary');
    Notification::assertSentTo($hr, HrAlert::class);
});

it('HR releases the COE, then records who received it', function () {
    Notification::fake();
    $user = coeUser('employee', 'COE2');
    coeEmployee('COE2');
    coeFile($user);
    $coe = CoeRequest::sole();
    $hr  = coeUser('hr_admin');

    Livewire::actingAs($hr)->test(CoeRequests::class)
        ->call('openAction', $coe->id, 'release')
        ->set('date', '2026-10-22')
        ->call('confirmAction')
        ->assertHasNoErrors();

    $coe->refresh();
    expect($coe->status)->toBe('released')->and($coe->released_by)->toBe($hr->id);
    Notification::assertSentTo($user, HrAlert::class);

    Livewire::actingAs($hr)->test(CoeRequests::class)
        ->call('openAction', $coe->id, 'receive')
        ->assertSet('receivedBy', 'Ana Reyes')
        ->set('date', '2026-10-23')
        ->call('confirmAction')
        ->assertHasNoErrors();

    $coe->refresh();
    expect($coe->status)->toBe('received')
        ->and($coe->received_by_name)->toBe('Ana Reyes')
        ->and($coe->received_at->toDateString())->toBe('2026-10-23');
});

it('declining requires a reason', function () {
    $user = coeUser('employee', 'COE3');
    coeEmployee('COE3');
    coeFile($user);
    $coe = CoeRequest::sole();

    Livewire::actingAs(coeUser('hr_admin'))->test(CoeRequests::class)
        ->call('openAction', $coe->id, 'reject')
        ->call('confirmAction')
        ->assertHasErrors('remarks');

    expect($coe->fresh()->status)->toBe('pending');
});

it('shows the form to the employee and HR only; employees cannot open the HR page', function () {
    $user = coeUser('employee', 'COE4');
    coeEmployee('COE4');
    coeFile($user);
    $coe = CoeRequest::sole();

    $this->actingAs($user)->get("/coe-request/{$coe->id}")->assertOk()->assertSee('COE REQUEST FORM')->assertSee('Bank loan application');
    $this->actingAs(coeUser('hr_admin'))->get("/coe-request/{$coe->id}")->assertOk();
    $this->actingAs(coeUser('hr_admin'))->get('/admin/coe-requests')->assertOk();
    $this->actingAs(coeUser('employee', 'COE5'))->get("/coe-request/{$coe->id}")->assertForbidden();
    $this->actingAs($user)->get('/admin/coe-requests')->assertForbidden();
    $this->actingAs($user)->get('/coe-request')->assertOk();
});
