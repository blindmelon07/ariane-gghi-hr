<?php

use App\Livewire\Admin\LeaveApprovals;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\SmsService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function makeUser(string $role): User
{
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function makeEmployee(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "EMP{$n}",
        'first_name' => 'Test',
        'last_name'  => "Employee{$n}",
        'is_active'  => true,
    ], $attrs));
}

function makeLeaveType(array $attrs = []): LeaveType
{
    static $n = 0;
    $n++;
    return LeaveType::create(array_merge([
        'code'               => "LT{$n}",
        'name'               => "Leave Type {$n}",
        'max_days_per_year'  => 15,
        'is_paid'            => true,
        'requires_approval'  => true,
    ], $attrs));
}

function makePendingRequest(Employee $emp, LeaveType $lt, int $step = 1): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => now()->addDay()->toDateString(),
        'end_date'      => now()->addDay()->toDateString(),
        'total_days'    => 1,
        'reason'        => 'Personal',
        'status'        => 'pending',
        'approval_step' => $step,
    ]);
}

// ── Route access ─────────────────────────────────────────────────────────────

describe('route access', function () {
    it('allows manager to access leave approvals page', function () {
        makeRole('manager');
        $this->actingAs(makeUser('manager'))
            ->get('/admin/leave')
            ->assertOk();
    });

    it('allows department_head to access leave approvals page', function () {
        makeRole('department_head');
        $this->actingAs(makeUser('department_head'))
            ->get('/admin/leave')
            ->assertOk();
    });

    it('allows hr_admin to access leave approvals page', function () {
        makeRole('hr_admin');
        $this->actingAs(makeUser('hr_admin'))
            ->get('/admin/leave')
            ->assertOk();
    });

    it('allows approver to access leave approvals page', function () {
        makeRole('approver');
        $this->actingAs(makeUser('approver'))
            ->get('/admin/leave')
            ->assertOk();
    });

    it('blocks employee from leave approvals page', function () {
        makeRole('employee');
        $this->actingAs(makeUser('employee'))
            ->get('/admin/leave')
            ->assertForbidden();
    });

    it('redirects guest to login', function () {
        $this->get('/admin/leave')->assertRedirect('/login');
    });
});

// ── Step resolution ───────────────────────────────────────────────────────────

describe('approval step resolution', function () {
    it('resolves manager to step 1', function () {
        $service = app(LeaveService::class);
        $user = makeUser('manager');
        expect($service->getStepForUser($user))->toBe(1);
    });

    it('resolves department_head to step 1', function () {
        $service = app(LeaveService::class);
        $user = makeUser('department_head');
        expect($service->getStepForUser($user))->toBe(1);
    });

    it('resolves hr_admin to step 2', function () {
        $service = app(LeaveService::class);
        $user = makeUser('hr_admin');
        expect($service->getStepForUser($user))->toBe(2);
    });

    it('resolves super_admin to step 3', function () {
        $service = app(LeaveService::class);
        $user = makeUser('super_admin');
        expect($service->getStepForUser($user))->toBe(3);
    });

    it('resolves approver to step 3', function () {
        $service = app(LeaveService::class);
        $user = makeUser('approver');
        expect($service->getStepForUser($user))->toBe(3);
    });

    it('returns null for employee role', function () {
        $service = app(LeaveService::class);
        $user = makeUser('employee');
        expect($service->getStepForUser($user))->toBeNull();
    });
});

// ── Component: pending requests filter ───────────────────────────────────────

describe('pending requests filter', function () {
    it('manager sees only requests at step 1', function () {
        makeRole('manager');
        $manager = makeUser('manager');
        $lt      = makeLeaveType();
        $emp     = makeEmployee();

        $step1 = makePendingRequest($emp, $lt, step: 1);
        $step2 = makePendingRequest($emp, $lt, step: 2);

        Livewire::actingAs($manager)
            ->test(LeaveApprovals::class)
            ->assertSee($step1->employee->full_name)
            ->assertDontSee('No leave requests found');
    });

    it('hr_admin sees only requests at step 2', function () {
        makeRole('hr_admin');
        $hr = makeUser('hr_admin');
        $lt  = makeLeaveType();
        $emp = makeEmployee();

        makePendingRequest($emp, $lt, step: 1);
        $step2 = makePendingRequest($emp, $lt, step: 2);

        $component = Livewire::actingAs($hr)->test(LeaveApprovals::class);
        expect($component->get('myStep'))->toBe(2);
    });
});

// ── Approve action ────────────────────────────────────────────────────────────

describe('approve action', function () {
    it('manager can approve a step-1 request and advances to step 2', function () {
        makeRole('manager');
        $manager = makeUser('manager');
        $lt      = makeLeaveType();
        $emp     = makeEmployee();
        $request = makePendingRequest($emp, $lt, step: 1);

        Livewire::actingAs($manager)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'approve')
            ->call('confirmAction');

        $request->refresh();
        expect($request->approval_step)->toBe(2)
            ->and($request->status)->toBe('pending');
    });

    it('final approval (step 3) marks request as approved', function () {
        makeRole('approver');
        $approver = makeUser('approver');
        $lt       = makeLeaveType();
        $emp      = makeEmployee();
        $request  = makePendingRequest($emp, $lt, step: 3);

        LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'year'          => now()->year,
            'total_credits' => 15,
            'used_credits'  => 0,
        ]);

        // Mock SMS so it doesn't make real HTTP calls
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        Livewire::actingAs($approver)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'approve')
            ->call('confirmAction');

        $request->refresh();
        expect($request->status)->toBe('approved')
            ->and($request->approval_step)->toBeNull();
    });

    it('blocks acting on a request that is not at your step', function () {
        makeRole('hr_admin');
        $hr      = makeUser('hr_admin');
        $lt      = makeLeaveType();
        $emp     = makeEmployee();
        $request = makePendingRequest($emp, $lt, step: 1); // hr is step 2

        Livewire::actingAs($hr)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'approve')
            ->call('confirmAction');

        $request->refresh();
        expect($request->status)->toBe('pending')
            ->and($request->approval_step)->toBe(1);
    });
});

// ── Reject action ─────────────────────────────────────────────────────────────

describe('reject action', function () {
    it('requires a reason when rejecting', function () {
        makeRole('manager');
        $manager = makeUser('manager');
        $lt      = makeLeaveType();
        $emp     = makeEmployee();
        $request = makePendingRequest($emp, $lt, step: 1);

        Livewire::actingAs($manager)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'reject')
            ->set('remarks', '')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        $request->refresh();
        expect($request->status)->toBe('pending');
    });

    it('manager can reject with a reason and request is marked rejected', function () {
        makeRole('manager');
        $manager = makeUser('manager');
        $lt      = makeLeaveType();
        $emp     = makeEmployee();
        $request = makePendingRequest($emp, $lt, step: 1);

        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        Livewire::actingAs($manager)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'reject')
            ->set('remarks', 'Insufficient staffing')
            ->call('confirmAction');

        $request->refresh();
        expect($request->status)->toBe('rejected')
            ->and($request->approval_step)->toBeNull();
    });
});

// ── SMS notification ──────────────────────────────────────────────────────────

describe('SMS notification on final approval', function () {
    it('sends SMS to employee with cell number on final approval', function () {
        makeRole('approver');
        $approver = makeUser('approver');
        $lt       = makeLeaveType();
        $emp      = makeEmployee(['cell_number' => '09171234567']);
        $request  = makePendingRequest($emp, $lt, step: 3);

        LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'year'          => now()->year,
            'total_credits' => 15,
            'used_credits'  => 0,
        ]);

        $smsMock = $this->mock(SmsService::class);
        $smsMock->shouldReceive('send')
            ->once()
            ->withArgs(fn ($number, $msg) =>
                $number === '09171234567' && str_contains($msg, 'APPROVED')
            )
            ->andReturn(true);

        Livewire::actingAs($approver)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'approve')
            ->call('confirmAction');
    });

    it('sends SMS with rejection reason when employee has cell number', function () {
        makeRole('manager');
        $manager = makeUser('manager');
        $lt      = makeLeaveType();
        $emp     = makeEmployee(['cell_number' => '09171234567']);
        $request = makePendingRequest($emp, $lt, step: 1);

        $smsMock = $this->mock(SmsService::class);
        $smsMock->shouldReceive('send')
            ->once()
            ->withArgs(fn ($number, $msg) =>
                str_contains($msg, 'REJECTED') && str_contains($msg, 'No budget')
            )
            ->andReturn(true);

        Livewire::actingAs($manager)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'reject')
            ->set('remarks', 'No budget')
            ->call('confirmAction');
    });

    it('skips SMS when employee has no cell number', function () {
        makeRole('approver');
        $approver = makeUser('approver');
        $lt       = makeLeaveType();
        $emp      = makeEmployee(['cell_number' => null]);
        $request  = makePendingRequest($emp, $lt, step: 3);

        LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'year'          => now()->year,
            'total_credits' => 15,
            'used_credits'  => 0,
        ]);

        $smsMock = $this->mock(SmsService::class);
        $smsMock->shouldNotReceive('send');

        Livewire::actingAs($approver)
            ->test(LeaveApprovals::class)
            ->call('openAction', $request->id, 'approve')
            ->call('confirmAction');
    });
});
