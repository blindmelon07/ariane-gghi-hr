<?php

use App\Jobs\SyncEmployeesJob;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\FleetManager;
use App\Livewire\Admin\LeaveApprovals;
use App\Livewire\Admin\TripTicketApprovals;
use App\Livewire\Employee\LeaveRequestForm;
use App\Livewire\Employee\TimeCorrectionForm;
use App\Livewire\Security\GuardPanel;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TripTicket;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SmsService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function findingsUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

// ── Admin dashboard quick actions ─────────────────────────────────────────────

it('hides HR-only quick actions from managers', function () {
    $this->actingAs(findingsUser('manager'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Process Payroll')
        ->assertDontSee('Sync BioTime Employees');
});

it('shows HR-only quick actions to hr admins', function () {
    $this->actingAs(findingsUser('hr_admin'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Process Payroll')
        ->assertSee('Sync BioTime Employees');
});

it('forbids non-HR roles from dispatching a BioTime sync', function (string $role) {
    Queue::fake();

    Livewire::actingAs(findingsUser($role))
        ->test(Dashboard::class)
        ->call('syncBioTime')
        ->assertForbidden();

    Queue::assertNotPushed(SyncEmployeesJob::class);
})->with(['manager', 'department_head', 'approver']);

it('lets hr admins dispatch a BioTime sync', function () {
    Queue::fake();

    Livewire::actingAs(findingsUser('hr_admin'))
        ->test(Dashboard::class)
        ->call('syncBioTime');

    Queue::assertPushed(SyncEmployeesJob::class);
});

// ── Mobile nav ────────────────────────────────────────────────────────────────

it('does not link security guards to employee-only pages', function () {
    $this->actingAs(findingsUser('security_guard'))
        ->get(route('security.fleet-return'))
        ->assertOk()
        ->assertDontSee(route('payslips.index'))
        ->assertDontSee(route('leave.request'));
});

// ── Deep-link date params ─────────────────────────────────────────────────────

it('ignores a malformed date deep-link on the leave form', function (string $date) {
    $this->actingAs(findingsUser('employee'))
        ->get(route('leave.request', ['type' => 'SL', 'date' => $date]))
        ->assertOk();
})->with(['null', 'garbage', '2026-13-45']);

it('still prefills a valid date deep-link', function () {
    Livewire::withQueryParams(['date' => '2030-01-15'])
        ->actingAs(findingsUser('employee'))
        ->test(LeaveRequestForm::class)
        ->assertSet('start_date', '2030-01-15')
        ->assertSet('end_date', '2030-01-15');

    Livewire::withQueryParams(['date' => 'null'])
        ->actingAs(findingsUser('employee'))
        ->test(TimeCorrectionForm::class)
        ->assertSet('date', '');
});

// ── Guard panel / fleet search ────────────────────────────────────────────────

function findingsTicket(array $attrs): TripTicket
{
    static $n = 0;
    $n++;
    $emp = Employee::create(['emp_code' => "PF{$n}", 'first_name' => 'Pat', 'last_name' => "Driver{$n}", 'is_active' => true]);

    return TripTicket::create(array_merge([
        'employee_id'        => $emp->id,
        'destination_from'   => 'GGHI',
        'destination_to'     => 'City Hall',
        'departure_datetime' => now()->addDay(),
        'purpose'            => 'Errand',
        'status'             => 'approved',
    ], $attrs));
}

function findingsVehicle(string $plate, string $model, string $status = 'available'): Vehicle
{
    return Vehicle::create([
        'plate_number' => $plate,
        'make'         => 'Toyota',
        'model'        => $model,
        'vehicle_type' => 'van',
        'capacity'     => 12,
        'status'       => $status,
        'is_active'    => true,
    ]);
}

it('keeps the guard panel search inside its status filters', function () {
    $van = findingsVehicle('NBX-1234', 'HiAce');
    findingsTicket(['vehicle_id' => $van->id, 'status' => 'completed']);
    findingsTicket(['vehicle_id' => $van->id, 'status' => 'pending']);
    $ready = findingsTicket(['vehicle_id' => $van->id]);
    $out   = findingsTicket(['vehicle_id' => $van->id, 'departed_at' => now()]);

    $panel = Livewire::actingAs(findingsUser('security_guard'))
        ->test(GuardPanel::class)
        ->set('search', 'NBX')
        ->instance();

    expect($panel->readyToDepart()->pluck('id')->all())->toBe([$ready->id])
        ->and($panel->currentlyOut()->pluck('id')->all())->toBe([$out->id]);
});

it('applies the vehicle status filter together with the search', function () {
    findingsVehicle('AAA-111', 'HiAce');
    findingsVehicle('BBB-222', 'Innova', 'under_maintenance');

    Livewire::actingAs(findingsUser('hr_admin'))
        ->test(FleetManager::class)
        ->set('search', 'Toyota')
        ->set('filterStatus', 'under_maintenance')
        ->assertSee('BBB-222')
        ->assertDontSee('AAA-111');
});

// ── Approval feedback ─────────────────────────────────────────────────────────

function findingsLeave(): LeaveRequest
{
    static $n = 0;
    $n++;
    $emp = Employee::create(['emp_code' => "PFL{$n}", 'first_name' => 'Leave', 'last_name' => "Filer{$n}", 'is_active' => true]);
    $lt  = LeaveType::create(['code' => "PFVL{$n}", 'name' => "Vacation Leave {$n}", 'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true]);

    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => now()->addDay()->toDateString(),
        'end_date'      => now()->addDay()->toDateString(),
        'total_days'    => 1,
        'reason'        => 'Personal',
        'status'        => 'pending',
        'approval_step' => 1,
    ]);
}

it('names the real next approver in the leave approval toast', function () {
    $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));
    $req = findingsLeave();

    $approve = fn (string $role) => Livewire::actingAs(findingsUser($role))
        ->test(LeaveApprovals::class)
        ->call('openAction', $req->id, 'approve')
        ->call('confirmAction');

    $approve('manager')->assertDispatched('toast', message: 'Approved — forwarded to HR.');
    $approve('hr_admin')->assertDispatched('toast', message: 'Approved — forwarded to CEO / Medical Director.');
    $approve('approver')->assertDispatched('toast', message: 'Leave fully approved.');

    expect($req->fresh()->status)->toBe('approved');
});

it('reopens the leave modal when a rejection has no reason', function () {
    $req = findingsLeave();

    Livewire::actingAs(findingsUser('manager'))
        ->test(LeaveApprovals::class)
        ->call('openAction', $req->id, 'reject')
        ->call('confirmAction')
        ->assertHasErrors('remarks')
        ->assertDispatched('open-action-modal');

    expect($req->fresh()->status)->toBe('pending');
});

it('renders toast messages in the app layout', function () {
    $this->actingAs(findingsUser('hr_admin'))
        ->get(route('admin.leave'))
        ->assertOk()
        ->assertSee('id="gghi-toasts"', false);
});

it('loads the payroll excel exports', function (string $export) {
    // A missing return type on collection() was a fatal error under maatwebsite/excel 4
    expect((new $export(1))->collection())->toBeInstanceOf(\Illuminate\Support\Collection::class);
})->with([\App\Exports\PayrollExport::class, \App\Exports\PayrollReportExport::class]);

it('stops hr admins approving their own trip ticket', function () {
    $hr     = findingsUser('hr_admin');
    $ticket = findingsTicket(['status' => 'pending', 'approval_step' => 3]);
    $ticket->employee->update(['emp_code' => $hr->employee_code]);

    Livewire::actingAs($hr)
        ->test(TripTicketApprovals::class)
        ->call('openAction', $ticket->id, 'approve')
        ->call('confirmAction')
        ->assertHasErrors('remarks');

    expect($ticket->fresh()->status)->toBe('pending');
});
