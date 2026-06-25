<?php

use App\Livewire\Admin\DriverManager;
use App\Livewire\Admin\FleetManager;
use App\Livewire\Admin\TripTicketApprovals;
use App\Livewire\Employee\TripTicketForm;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\TripTicket;
use App\Models\TripTicketApproval;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripTicketService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function fleetRole(string $role): void
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
}

function fleetUser(string $role): User
{
    fleetRole($role);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function fleetEmployee(User $user): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'   => $user->employee_code ?? "FT{$n}",
        'first_name' => $user->name,
        'last_name'  => 'Staff',
        'is_active'  => true,
        'department' => 'Nursing',
    ]);
}

function fleetEmpOnly(): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'   => "FTE{$n}",
        'first_name' => 'Fleet',
        'last_name'  => "Test{$n}",
        'is_active'  => true,
        'department' => 'Admin',
    ]);
}

function fleetVehicle(array $attrs = []): Vehicle
{
    static $n = 0;
    $n++;
    return Vehicle::create(array_merge([
        'plate_number' => "FLT-{$n}00{$n}",
        'make'         => 'Toyota',
        'model'        => 'Fortuner',
        'vehicle_type' => 'SUV',
        'capacity'     => 7,
        'status'       => 'available',
        'is_active'    => true,
    ], $attrs));
}

function fleetDriver(Employee $emp, array $attrs = []): Driver
{
    static $n = 0;
    $n++;
    return Driver::create(array_merge([
        'employee_id'    => $emp->id,
        'license_number' => "N01-{$n}-999999",
        'is_active'      => true,
    ], $attrs));
}

function fleetTicket(Employee $emp, int $step = 1, array $attrs = []): TripTicket
{
    return TripTicket::create(array_merge([
        'employee_id'        => $emp->id,
        'department'         => $emp->department ?? 'Nursing',
        'destination_from'   => 'GGHI Main Campus',
        'destination_to'     => 'DOH Regional Office',
        'departure_datetime' => now()->addDay(),
        'purpose'            => 'Procurement of medical supplies',
        'status'             => 'pending',
        'approval_step'      => $step,
    ], $attrs));
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('employee can access trip-ticket request page', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/trip-ticket')->assertOk();
    });

    it('employee can access my-tickets page', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/trip-ticket/my-tickets')->assertOk();
    });

    it('hr_admin can access admin fleet requests page', function () {
        $this->actingAs(fleetUser('hr_admin'))->get('/admin/fleet/requests')->assertOk();
    });

    it('hr_admin can access admin fleet vehicles page', function () {
        $this->actingAs(fleetUser('hr_admin'))->get('/admin/fleet/vehicles')->assertOk();
    });

    it('hr_admin can access admin fleet drivers page', function () {
        $this->actingAs(fleetUser('hr_admin'))->get('/admin/fleet/drivers')->assertOk();
    });

    it('manager can access admin fleet requests page', function () {
        $this->actingAs(fleetUser('manager'))->get('/admin/fleet/requests')->assertOk();
    });

    it('manager cannot access admin fleet vehicles page', function () {
        $this->actingAs(fleetUser('manager'))->get('/admin/fleet/vehicles')->assertForbidden();
    });

    it('guest is redirected to login on all fleet routes', function () {
        $this->get('/trip-ticket')->assertRedirect('/login');
        $this->get('/admin/fleet/requests')->assertRedirect('/login');
        $this->get('/admin/fleet/vehicles')->assertRedirect('/login');
    });
});

// ── TripTicketService — getInitialStep ────────────────────────────────────────

describe('TripTicketService getInitialStep', function () {
    it('employee starts at step 1', function () {
        $user = fleetUser('employee');
        expect(app(TripTicketService::class)->getInitialStep($user))->toBe(1);
    });

    it('manager starts at step 2 (skips immediate head)', function () {
        $user = fleetUser('manager');
        expect(app(TripTicketService::class)->getInitialStep($user))->toBe(2);
    });

    it('department_head starts at step 2', function () {
        $user = fleetUser('department_head');
        expect(app(TripTicketService::class)->getInitialStep($user))->toBe(2);
    });

    it('hr_admin starts at step 3 (fleet only)', function () {
        $user = fleetUser('hr_admin');
        expect(app(TripTicketService::class)->getInitialStep($user))->toBe(3);
    });

    it('super_admin starts at step 3', function () {
        $user = fleetUser('super_admin');
        expect(app(TripTicketService::class)->getInitialStep($user))->toBe(3);
    });
});

// ── TripTicketService — getApplicableSteps ────────────────────────────────────

describe('TripTicketService getApplicableSteps', function () {
    it('manager can act at step 1 only', function () {
        $user = fleetUser('manager');
        expect(app(TripTicketService::class)->getApplicableSteps($user))->toBe([1]);
    });

    it('department_head can act at step 1 only', function () {
        $user = fleetUser('department_head');
        expect(app(TripTicketService::class)->getApplicableSteps($user))->toBe([1]);
    });

    it('hr_admin can act at step 2 AND step 3', function () {
        $user = fleetUser('hr_admin');
        expect(app(TripTicketService::class)->getApplicableSteps($user))->toBe([2, 3]);
    });

    it('super_admin can act at step 3 only', function () {
        $user = fleetUser('super_admin');
        expect(app(TripTicketService::class)->getApplicableSteps($user))->toBe([3]);
    });

    it('employee has no applicable steps', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        expect(app(TripTicketService::class)->getApplicableSteps($user))->toBe([]);
    });
});

// ── Employee filing a trip ticket ─────────────────────────────────────────────

describe('employee filing a trip ticket', function () {
    it('valid submission creates a TripTicket at step 1', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $emp  = fleetEmployee($user);

        Livewire::actingAs($user)
            ->test(TripTicketForm::class)
            ->set('destination_from', 'GGHI Main Campus')
            ->set('destination_to', 'DOH Regional Office')
            ->set('departure_datetime', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('purpose', 'Procurement of medical supplies for ICU ward')
            ->call('submit');

        $ticket = TripTicket::where('employee_id', $emp->id)->first();
        expect($ticket)->not->toBeNull()
            ->and($ticket->status)->toBe('pending')
            ->and($ticket->approval_step)->toBe(1)
            ->and($ticket->destination_to)->toBe('DOH Regional Office');
    });

    it('requires destination_from, destination_to, departure_datetime, and purpose', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');

        Livewire::actingAs($user)
            ->test(TripTicketForm::class)
            ->call('submit')
            ->assertHasErrors(['destination_from', 'destination_to', 'departure_datetime', 'purpose']);
    });

    it('purpose must be at least 10 characters', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');

        Livewire::actingAs($user)
            ->test(TripTicketForm::class)
            ->set('destination_from', 'GGHI')
            ->set('destination_to', 'DOH')
            ->set('departure_datetime', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('purpose', 'Too short')
            ->call('submit')
            ->assertHasErrors('purpose');
    });

    it('manager files at step 2 (skips immediate head)', function () {
        $manager = fleetUser('manager');
        $emp     = fleetEmployee($manager);

        Livewire::actingAs($manager)
            ->test(TripTicketForm::class)
            ->set('destination_from', 'GGHI Main Campus')
            ->set('destination_to', 'City Health Office')
            ->set('departure_datetime', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('purpose', 'Meeting with health officials regarding budget review')
            ->call('submit');

        $ticket = TripTicket::where('employee_id', $emp->id)->first();
        expect($ticket->approval_step)->toBe(2);
    });

    it('hr_admin files directly at step 3 (fleet only)', function () {
        $hr  = fleetUser('hr_admin');
        $emp = fleetEmployee($hr);

        Livewire::actingAs($hr)
            ->test(TripTicketForm::class)
            ->set('destination_from', 'GGHI Main Campus')
            ->set('destination_to', 'PhilHealth Office')
            ->set('departure_datetime', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('purpose', 'Submission of employee contribution reports to PhilHealth office')
            ->call('submit');

        $ticket = TripTicket::where('employee_id', $emp->id)->first();
        expect($ticket->approval_step)->toBe(3);
    });
});

// ── Approval chain (step 1 → 2 → 3) ──────────────────────────────────────────

describe('full approval chain (step 1 → 2 → 3)', function () {
    beforeEach(function () {
        $this->service  = app(TripTicketService::class);
        $this->deptHead = fleetUser('department_head');
        $this->hr       = fleetUser('hr_admin');
        $this->ceo      = fleetUser('super_admin');
        $empUser        = fleetUser('employee');
        $this->emp      = fleetEmployee($empUser);
        $this->ticket   = fleetTicket($this->emp, step: 1);
    });

    it('starts at approval_step 1', function () {
        expect($this->ticket->approval_step)->toBe(1)
            ->and($this->ticket->status)->toBe('pending');
    });

    it('step 1: dept_head approves → advances to step 2', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();

        expect($this->ticket->approval_step)->toBe(2)
            ->and($this->ticket->status)->toBe('pending');
    });

    it('step 1 approval is recorded with correct label "Immediate Head"', function () {
        $this->service->approve($this->ticket, $this->deptHead, 'Looks good');
        $this->ticket->refresh();

        $approval = TripTicketApproval::where('trip_ticket_id', $this->ticket->id)
            ->where('step', 1)->first();

        expect($approval)->not->toBeNull()
            ->and($approval->label)->toBe('Immediate Head')
            ->and($approval->action)->toBe('approved');
    });

    it('step 2: hr approves (certifies driver) → advances to step 3', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->hr);
        $this->ticket->refresh();

        expect($this->ticket->approval_step)->toBe(3)
            ->and($this->ticket->status)->toBe('pending');
    });

    it('step 2 approval is recorded with label "HR Officer"', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->hr, 'Driver certified fit');
        $this->ticket->refresh();

        $approval = TripTicketApproval::where('trip_ticket_id', $this->ticket->id)
            ->where('step', 2)->first();

        expect($approval->label)->toBe('HR Officer')
            ->and($approval->action)->toBe('approved');
    });

    it('step 3: super_admin approves → ticket fully approved (scheduled)', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->hr);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->ceo);
        $this->ticket->refresh();

        expect($this->ticket->status)->toBe('approved')
            ->and($this->ticket->approval_step)->toBeNull()
            ->and($this->ticket->approved_by)->toBe($this->ceo->id);
    });

    it('full chain creates 3 approval records', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->hr);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->ceo);

        expect(TripTicketApproval::where('trip_ticket_id', $this->ticket->id)->count())->toBe(3);
    });

    it('hr_admin can also be the final fleet approver at step 3', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();
        $this->service->approve($this->ticket, $this->hr);
        $this->ticket->refresh();

        // Now at step 3 — hr_admin approves as fleet
        $this->service->approve($this->ticket, $this->hr);
        $this->ticket->refresh();

        expect($this->ticket->status)->toBe('approved')
            ->and($this->ticket->approved_by)->toBe($this->hr->id);
    });

    it('dept_head cannot act on step 2 or 3', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();

        $steps = $this->service->getApplicableSteps($this->deptHead);
        expect($steps)->toBe([1])
            ->and(in_array($this->ticket->approval_step, $steps))->toBeFalse();
    });

    it('reject at step 1 terminates chain immediately', function () {
        $this->service->reject($this->ticket, $this->deptHead, 'Trip not approved by department head');
        $this->ticket->refresh();

        expect($this->ticket->status)->toBe('rejected')
            ->and($this->ticket->approval_step)->toBeNull();
    });

    it('reject at step 2 terminates chain without reaching fleet', function () {
        $this->service->approve($this->ticket, $this->deptHead);
        $this->ticket->refresh();

        $this->service->reject($this->ticket, $this->hr, 'Driver not medically cleared');
        $this->ticket->refresh();

        expect($this->ticket->status)->toBe('rejected')
            ->and($this->ticket->approval_step)->toBeNull();
    });
});

// ── TripTicketApprovals Livewire component ────────────────────────────────────

describe('TripTicketApprovals component', function () {
    it('dept_head sees pending step-1 tickets', function () {
        $deptHead = fleetUser('department_head');
        $emp      = fleetEmpOnly();
        fleetTicket($emp, step: 1);

        Livewire::actingAs($deptHead)
            ->test(TripTicketApprovals::class)
            ->assertSee('DOH Regional Office');
    });

    it('dept_head does NOT see step-2 pending tickets', function () {
        $deptHead = fleetUser('department_head');
        $emp      = fleetEmpOnly();
        fleetTicket($emp, step: 2);

        Livewire::actingAs($deptHead)
            ->test(TripTicketApprovals::class)
            ->assertDontSee('DOH Regional Office');
    });

    it('hr_admin sees both step-2 and step-3 pending tickets', function () {
        $hr   = fleetUser('hr_admin');
        $emp1 = fleetEmpOnly();
        $emp2 = fleetEmpOnly();

        fleetTicket($emp1, step: 2, attrs: ['destination_to' => 'PhilHealth Office']);
        fleetTicket($emp2, step: 3, attrs: ['destination_to' => 'BIR Main Office']);

        Livewire::actingAs($hr)
            ->test(TripTicketApprovals::class)
            ->assertSee('PhilHealth Office')
            ->assertSee('BIR Main Office');
    });

    it('reject requires a reason', function () {
        $deptHead = fleetUser('department_head');
        $emp      = fleetEmpOnly();
        $ticket   = fleetTicket($emp, step: 1);

        Livewire::actingAs($deptHead)
            ->test(TripTicketApprovals::class)
            ->call('openAction', $ticket->id, 'reject')
            ->set('remarks', '')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        $ticket->refresh();
        expect($ticket->status)->toBe('pending');
    });

    it('dept_head approves step-1 via component and advances to step 2', function () {
        $deptHead = fleetUser('department_head');
        $emp      = fleetEmpOnly();
        $ticket   = fleetTicket($emp, step: 1);

        Livewire::actingAs($deptHead)
            ->test(TripTicketApprovals::class)
            ->call('openAction', $ticket->id, 'approve')
            ->call('confirmAction');

        $ticket->refresh();
        expect($ticket->approval_step)->toBe(2)
            ->and($ticket->status)->toBe('pending');
    });

    it('super_admin fully approves at step 3 via component', function () {
        $ceo    = fleetUser('super_admin');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, step: 3);

        Livewire::actingAs($ceo)
            ->test(TripTicketApprovals::class)
            ->call('openAction', $ticket->id, 'approve')
            ->set('remarks', 'Vehicle assigned and ready')
            ->call('confirmAction');

        $ticket->refresh();
        expect($ticket->status)->toBe('approved')
            ->and($ticket->approved_by)->toBe($ceo->id);
    });

    it('component blocks action when request is not at the user step', function () {
        $deptHead = fleetUser('department_head');
        $emp      = fleetEmpOnly();
        $ticket   = fleetTicket($emp, step: 2); // step 2, not step 1

        Livewire::actingAs($deptHead)
            ->test(TripTicketApprovals::class)
            ->call('openAction', $ticket->id, 'approve')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        $ticket->refresh();
        expect($ticket->status)->toBe('pending')
            ->and($ticket->approval_step)->toBe(2);
    });
});

// ── Vehicle return / trip completion ─────────────────────────────────────────

describe('vehicle return and trip completion', function () {
    it('approved ticket can be marked as returned by hr_admin', function () {
        $hr      = fleetUser('hr_admin');
        $emp     = fleetEmpOnly();
        $vehicle = fleetVehicle(['status' => 'in_use']);
        $ticket  = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'vehicle_id' => $vehicle->id]);

        app(TripTicketService::class)->complete($ticket, $hr);

        expect($ticket->fresh()->status)->toBe('completed')
            ->and($ticket->fresh()->completed_at)->not->toBeNull()
            ->and($ticket->fresh()->completed_by)->toBe($hr->id);
    });

    it('completing a trip releases the vehicle back to available', function () {
        $hr      = fleetUser('hr_admin');
        $emp     = fleetEmpOnly();
        $vehicle = fleetVehicle(['status' => 'in_use']);
        $ticket  = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'vehicle_id' => $vehicle->id]);

        app(TripTicketService::class)->complete($ticket, $hr);

        expect($vehicle->fresh()->status)->toBe('available');
    });

    it('final approval sets vehicle status to in_use', function () {
        $service = app(TripTicketService::class);
        $ceo     = fleetUser('super_admin');
        $emp     = fleetEmpOnly();
        $vehicle = fleetVehicle(['status' => 'available']);
        $ticket  = fleetTicket($emp, step: 3, attrs: ['vehicle_id' => $vehicle->id]);

        $service->approve($ticket, $ceo);

        expect($vehicle->fresh()->status)->toBe('in_use');
    });

    it('completing a ticket with no vehicle does not error', function () {
        $hr     = fleetUser('hr_admin');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'vehicle_id' => null]);

        app(TripTicketService::class)->complete($ticket, $hr);

        expect($ticket->fresh()->status)->toBe('completed');
    });

    it('hr_admin can mark returned via the Livewire component', function () {
        $hr      = fleetUser('hr_admin');
        $emp     = fleetEmpOnly();
        $vehicle = fleetVehicle(['status' => 'in_use']);
        $ticket  = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'vehicle_id' => $vehicle->id]);

        Livewire::actingAs($hr)
            ->test(TripTicketApprovals::class)
            ->set('filterStatus', 'approved')
            ->call('markReturned', $ticket->id);

        expect($ticket->fresh()->status)->toBe('completed')
            ->and($vehicle->fresh()->status)->toBe('available');
    });

    it('pending ticket cannot be marked as returned', function () {
        $hr     = fleetUser('hr_admin');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, step: 1);

        app(TripTicketService::class)->complete($ticket, $hr);

        expect($ticket->fresh()->status)->toBe('pending');
    });
});

// ── Security Guard panel ──────────────────────────────────────────────────────

describe('security guard panel', function () {
    it('security_guard can access the fleet-return page', function () {
        fleetRole('security_guard');
        $guard = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $this->actingAs($guard)->get('/security/fleet-return')->assertOk();
    });

    it('employee cannot access the fleet-return page', function () {
        fleetRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/security/fleet-return')->assertForbidden();
    });

    it('guard panel shows only approved (in-use) trips', function () {
        fleetRole('security_guard');
        $guard = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp = fleetEmpOnly();

        fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'destination_to' => 'Active Trip Dest']);
        fleetTicket($emp, step: 1, attrs: ['destination_to' => 'Pending Trip Dest']);
        fleetTicket($emp, attrs: ['status' => 'completed', 'approval_step' => null, 'destination_to' => 'Done Trip Dest']);

        Livewire::actingAs($guard)
            ->test(\App\Livewire\Security\GuardPanel::class)
            ->assertSee('Active Trip Dest')
            ->assertDontSee('Pending Trip Dest')
            ->assertDontSee('Done Trip Dest');
    });

    it('guard can mark a vehicle as returned with remarks', function () {
        fleetRole('security_guard');
        $guard   = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp     = fleetEmpOnly();
        $vehicle = fleetVehicle(['status' => 'in_use']);
        $ticket  = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'vehicle_id' => $vehicle->id]);

        Livewire::actingAs($guard)
            ->test(\App\Livewire\Security\GuardPanel::class)
            ->call('openAction', $ticket->id, 'return')
            ->set('return_remarks', 'Vehicle in good condition. Odometer: 45,230 km.')
            ->call('confirmAction');

        expect($ticket->fresh()->status)->toBe('completed')
            ->and($ticket->fresh()->return_remarks)->toBe('Vehicle in good condition. Odometer: 45,230 km.')
            ->and($ticket->fresh()->completed_by)->toBe($guard->id)
            ->and($vehicle->fresh()->status)->toBe('available');
    });

    it('guard can mark returned without remarks', function () {
        fleetRole('security_guard');
        $guard  = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null]);

        Livewire::actingAs($guard)
            ->test(\App\Livewire\Security\GuardPanel::class)
            ->call('openAction', $ticket->id, 'return')
            ->call('confirmAction');

        expect($ticket->fresh()->status)->toBe('completed');
    });

    it('guard can confirm departure — sets departed_at and departed_by', function () {
        fleetRole('security_guard');
        $guard  = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null]);

        Livewire::actingAs($guard)
            ->test(\App\Livewire\Security\GuardPanel::class)
            ->call('openAction', $ticket->id, 'depart')
            ->call('confirmAction');

        expect($ticket->fresh()->departed_at)->not->toBeNull()
            ->and($ticket->fresh()->departed_by)->toBe($guard->id)
            ->and($ticket->fresh()->status)->toBe('approved'); // still approved, just departed
    });

    it('departed ticket moves to currentlyOut section', function () {
        fleetRole('security_guard');
        $guard  = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp    = fleetEmpOnly();
        fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'departed_at' => now(), 'departed_by' => $guard->id, 'destination_to' => 'Currently Out Dest']);
        fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'destination_to' => 'Ready Depart Dest']);

        $component = Livewire::actingAs($guard)->test(\App\Livewire\Security\GuardPanel::class);

        expect($component->get('readyToDepart')->where('destination_to', 'Ready Depart Dest')->count())->toBe(1)
            ->and($component->get('currentlyOut')->where('destination_to', 'Currently Out Dest')->count())->toBe(1);
    });

    it('cannot depart a ticket that has already departed', function () {
        fleetRole('security_guard');
        $guard  = User::factory()->create(['role' => 'security_guard']);
        $guard->syncRoles('security_guard');
        $emp    = fleetEmpOnly();
        $dept   = now()->subHour();
        $ticket = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null, 'departed_at' => $dept]);

        app(TripTicketService::class)->depart($ticket, $guard);

        // departed_at should not change
        expect($ticket->fresh()->departed_at->toDateTimeString())->toBe($dept->toDateTimeString());
    });

    it('return_remarks is stored on the trip ticket', function () {
        $hr     = fleetUser('hr_admin');
        $emp    = fleetEmpOnly();
        $ticket = fleetTicket($emp, attrs: ['status' => 'approved', 'approval_step' => null]);

        app(TripTicketService::class)->complete($ticket, $hr, 'Minor scratch on rear bumper noted.');

        expect($ticket->fresh()->return_remarks)->toBe('Minor scratch on rear bumper noted.');
    });
});

// ── FleetManager (Vehicle CRUD) ───────────────────────────────────────────────

describe('FleetManager vehicle management', function () {
    it('hr_admin can add a vehicle', function () {
        $hr = fleetUser('hr_admin');

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('openCreate')
            ->set('plate_number', 'XYZ-1234')
            ->set('make', 'Toyota')
            ->set('model', 'HiAce')
            ->set('vehicle_type', 'van')
            ->set('capacity', 12)
            ->set('year', '2023')
            ->set('status', 'available')
            ->call('save');

        expect(Vehicle::where('plate_number', 'XYZ-1234')->exists())->toBeTrue();
    });

    it('plate_number is required', function () {
        $hr = fleetUser('hr_admin');

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('openCreate')
            ->set('make', 'Toyota')
            ->set('model', 'HiAce')
            ->call('save')
            ->assertHasErrors('plate_number');
    });

    it('make and model are required', function () {
        $hr = fleetUser('hr_admin');

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('openCreate')
            ->set('plate_number', 'AAA-1111')
            ->call('save')
            ->assertHasErrors(['make', 'model']);
    });

    it('hr_admin can edit a vehicle', function () {
        $hr      = fleetUser('hr_admin');
        $vehicle = fleetVehicle();

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('openEdit', $vehicle->id)
            ->set('status', 'under_maintenance')
            ->call('save');

        expect($vehicle->fresh()->status)->toBe('under_maintenance');
    });

    it('hr_admin can toggle vehicle active status', function () {
        $hr      = fleetUser('hr_admin');
        $vehicle = fleetVehicle(['is_active' => true]);

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('toggleActive', $vehicle->id);

        expect($vehicle->fresh()->is_active)->toBeFalse();
    });

    it('plate_number is uppercased on save', function () {
        $hr = fleetUser('hr_admin');

        Livewire::actingAs($hr)
            ->test(FleetManager::class)
            ->call('openCreate')
            ->set('plate_number', 'abc-9999')
            ->set('make', 'Isuzu')
            ->set('model', 'Crosswind')
            ->set('vehicle_type', 'SUV')
            ->set('capacity', 7)
            ->call('save');

        expect(Vehicle::where('plate_number', 'ABC-9999')->exists())->toBeTrue();
    });
});

// ── DriverManager (Driver CRUD) ───────────────────────────────────────────────

describe('DriverManager driver management', function () {
    it('hr_admin can add a driver', function () {
        $hr  = fleetUser('hr_admin');
        $emp = fleetEmpOnly();

        Livewire::actingAs($hr)
            ->test(DriverManager::class)
            ->call('openCreate')
            ->set('employee_id', $emp->id)
            ->set('license_number', 'N01-23-123456')
            ->set('license_expiry', '2027-12-31')
            ->set('medical_clearance_date', '2026-06-01')
            ->call('save');

        expect(Driver::where('employee_id', $emp->id)->exists())->toBeTrue();
    });

    it('license_number is required', function () {
        $hr  = fleetUser('hr_admin');
        $emp = fleetEmpOnly();

        Livewire::actingAs($hr)
            ->test(DriverManager::class)
            ->call('openCreate')
            ->set('employee_id', $emp->id)
            ->call('save')
            ->assertHasErrors('license_number');
    });

    it('employee_id must exist in employees table', function () {
        $hr = fleetUser('hr_admin');

        Livewire::actingAs($hr)
            ->test(DriverManager::class)
            ->call('openCreate')
            ->set('employee_id', 99999)
            ->set('license_number', 'N01-23-999999')
            ->call('save')
            ->assertHasErrors('employee_id');
    });

    it('hr_admin can edit a driver license expiry', function () {
        $hr     = fleetUser('hr_admin');
        $emp    = fleetEmpOnly();
        $driver = fleetDriver($emp, ['license_expiry' => '2026-12-31']);

        Livewire::actingAs($hr)
            ->test(DriverManager::class)
            ->call('openEdit', $driver->id)
            ->set('license_expiry', '2028-06-30')
            ->call('save');

        expect($driver->fresh()->license_expiry->format('Y-m-d'))->toBe('2028-06-30');
    });

    it('hr_admin can toggle driver active status', function () {
        $hr     = fleetUser('hr_admin');
        $emp    = fleetEmpOnly();
        $driver = fleetDriver($emp, ['is_active' => true]);

        Livewire::actingAs($hr)
            ->test(DriverManager::class)
            ->call('toggleActive', $driver->id);

        expect($driver->fresh()->is_active)->toBeFalse();
    });
});
