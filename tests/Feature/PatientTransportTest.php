<?php

use App\Livewire\Admin\PatientTransportApprovals;
use App\Livewire\Employee\PatientTransportForm;
use App\Livewire\Security\PatientTransportLog;
use App\Models\Employee;
use App\Models\PatientTransport;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 09:00:00', 'Asia/Manila'));
});

function ptUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function ptEmployee(string $code, ?string $position): Employee
{
    // position is normally derived from position_id on save; set it directly for the test
    $emp = Employee::create(['emp_code' => $code, 'first_name' => 'Amb', 'last_name' => $code, 'is_active' => true]);
    $emp->forceFill(['position' => $position])->saveQuietly();
    return $emp;
}

function ptSlip(Employee $emp, string $status = 'pending'): PatientTransport
{
    return PatientTransport::create([
        'employee_id' => $emp->id, 'date' => '2026-10-22', 'patient_name' => 'Juan Dela Cruz',
        'transported_to' => 'Bicol Medical Center', 'plate_number' => 'AMB-123', 'driver_name' => 'Pedro Driver',
        'medical_staff' => "Nurse Ana\nNurse Ben", 'status' => $status, 'approval_step' => $status === 'pending' ? 1 : null,
    ])->fresh();
}

it('only Drivers and Institutional Workers can open the form and see the menu link', function () {
    $driver = ptUser('employee', 'PT1');
    ptEmployee('PT1', 'Driver');
    $worker = ptUser('employee', 'PT2');
    ptEmployee('PT2', ' institutional worker ');
    $nurse  = ptUser('employee', 'PT3');
    ptEmployee('PT3', 'Staff Nurse');

    $this->actingAs($driver)->get('/patient-transport')->assertOk();
    $this->actingAs($worker)->get('/patient-transport')->assertOk();
    $this->actingAs($nurse)->get('/patient-transport')->assertForbidden();

    $this->actingAs($driver)->get('/dashboard')->assertSee('Patient Transport');
    $this->actingAs($nurse)->get('/dashboard')->assertDontSee('Patient Transport');
});

it('files a numbered slip, prefilling the driver with the filer', function () {
    $user = ptUser('employee', 'PT4');
    $emp  = ptEmployee('PT4', 'Driver');

    Livewire::actingAs($user)->test(PatientTransportForm::class)
        ->assertSet('driverName', $emp->full_name)
        ->set('patientName', 'Juan Dela Cruz')
        ->set('transportedTo', 'Bicol Medical Center')
        ->set('plateNumber', 'amb-123')
        ->set('medicalStaff', "Nurse Ana\nNurse Ben")
        ->call('submit')
        ->assertHasNoErrors();

    $slip = PatientTransport::sole();
    expect($slip->series_no)->toBe(sprintf('PT-2026-%05d', $slip->id))
        ->and($slip->plate_number)->toBe('AMB-123')
        ->and($slip->medicalStaffList())->toBe(['Nurse Ana', 'Nurse Ben']);
});

it('is approved by HR, and only approved slips reach the guard', function () {
    $slip = ptSlip(ptEmployee('PT5', 'Driver'));
    $guard = ptUser('security_guard');

    Livewire::actingAs($guard)->test(PatientTransportLog::class)
        ->assertDontSee('AMB-123')
        ->call('depart', $slip->id);
    expect($slip->fresh()->departed_at)->toBeNull();   // not approved yet

    Livewire::actingAs(ptUser('hr_admin'))->test(PatientTransportApprovals::class)
        ->call('openAction', $slip->id, 'approve')
        ->call('confirmAction')
        ->assertHasNoErrors();
    expect($slip->fresh()->status)->toBe('approved');

    Livewire::actingAs($guard)->test(PatientTransportLog::class)
        ->assertSee('AMB-123')
        ->call('depart', $slip->id)
        ->call('arrive', $slip->id);

    $slip->refresh();
    expect($slip->departed_by)->toBe($guard->id)
        ->and($slip->arrived_by)->toBe($guard->id)
        ->and($slip->status)->toBe('completed');
});

it('shows the slip with recorded times to the filer and guard only', function () {
    $owner = ptUser('employee', 'PT6');
    $slip  = ptSlip(ptEmployee('PT6', 'Driver'), 'approved');
    $slip->update(['departed_at' => '2026-10-22 01:15:00', 'status' => 'approved']);   // 09:15 Manila

    $this->actingAs($owner)->get("/patient-transport/{$slip->id}")
        ->assertOk()->assertSee('APPROVED')->assertSee('09:15 AM')->assertSee('Nurse Ben');
    $this->actingAs(ptUser('security_guard'))->get("/patient-transport/{$slip->id}")->assertOk();
    $this->actingAs(ptUser('employee', 'PT7'))->get("/patient-transport/{$slip->id}")->assertForbidden();
});
