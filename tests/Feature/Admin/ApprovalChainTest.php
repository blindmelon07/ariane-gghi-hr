<?php

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\SmsService;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function chainRole(string $role): void
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
}

function chainUser(string $role): User
{
    chainRole($role);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function chainEmp(User $user, array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "CHN{$n}",
        'first_name' => $user->name,
        'last_name'  => 'Test',
        'is_active'  => true,
    ], $attrs));
}

function chainLt(): LeaveType
{
    static $n = 0;
    $n++;
    return LeaveType::create([
        'code'              => "CLT{$n}",
        'name'              => "ChainLeave{$n}",
        'max_days_per_year' => 15,
        'is_paid'           => true,
        'requires_approval' => true,
    ]);
}

function chainCredit(Employee $emp, LeaveType $lt, float $total = 15): LeaveCredit
{
    return LeaveCredit::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'year'          => now()->year,
        'total_credits' => $total,
        'used_credits'  => 0,
    ]);
}

function chainRequest(Employee $emp, LeaveType $lt, int $step): LeaveRequest
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

// ── getInitialStep ────────────────────────────────────────────────────────────

describe('getInitialStep', function () {
    it('employee starts at step 1', function () {
        $user = chainUser('employee');
        expect(app(LeaveService::class)->getInitialStep($user))->toBe(1);
    });

    it('department_head starts at step 2 (skips dept head step)', function () {
        $user = chainUser('department_head');
        expect(app(LeaveService::class)->getInitialStep($user))->toBe(2);
    });

    it('manager starts at step 3 (goes directly to CEO/MD)', function () {
        $user = chainUser('manager');
        expect(app(LeaveService::class)->getInitialStep($user))->toBe(3);
    });
});

// ── Employee full chain: Dept Head → HR → CEO/MD ──────────────────────────────

describe('employee approval chain (step 1 → 2 → 3)', function () {
    beforeEach(function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));
        $this->service      = app(LeaveService::class);
        $this->deptHead     = chainUser('department_head');
        $this->hr           = chainUser('hr_admin');
        $this->ceo          = chainUser('super_admin');
        $this->lt           = chainLt();
        $empUser            = chainUser('employee');
        $this->emp          = chainEmp($empUser);
        chainCredit($this->emp, $this->lt);
        $this->request      = chainRequest($this->emp, $this->lt, step: 1);
    });

    it('starts at approval_step 1', function () {
        expect($this->request->approval_step)->toBe(1)
            ->and($this->request->status)->toBe('pending');
    });

    it('step 1: dept_head approves → advances to step 2', function () {
        $this->service->approve($this->request, $this->deptHead);

        $this->request->refresh();
        expect($this->request->approval_step)->toBe(2)
            ->and($this->request->status)->toBe('pending');
    });

    it('step 1 approval is recorded with correct label', function () {
        $this->service->approve($this->request, $this->deptHead, 'Approved');

        $approval = LeaveRequestApproval::where('leave_request_id', $this->request->id)
            ->where('step', 1)->first();

        expect($approval)->not->toBeNull()
            ->and($approval->label)->toBe('Department Head')
            ->and($approval->action)->toBe('approved');
    });

    it('step 2: hr approves → advances to step 3', function () {
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();

        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();

        expect($this->request->approval_step)->toBe(3)
            ->and($this->request->status)->toBe('pending');
    });

    it('step 3: CEO approves → request fully approved', function () {
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);
        $this->request->refresh();

        expect($this->request->status)->toBe('approved')
            ->and($this->request->approval_step)->toBeNull()
            ->and($this->request->approved_by)->toBe($this->ceo->id);
    });

    it('full chain creates 3 approval records', function () {
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);

        expect(LeaveRequestApproval::where('leave_request_id', $this->request->id)->count())->toBe(3);
    });

    it('deducts leave credits after full approval', function () {
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);

        $credit = LeaveCredit::where('employee_id', $this->emp->id)
            ->where('leave_type_id', $this->lt->id)
            ->first();

        expect((float) $credit->used_credits)->toBe(1.0);
    });

    it('dept_head cannot act on step 2 request', function () {
        // Advance to step 2 first
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();

        $stepBefore = $this->request->approval_step;

        // Dept head tries to approve again at step 2 — getStepForUser returns 1, not 2
        // LeaveApprovals component guard blocks this, but let's verify approval_step doesn't change
        // by simulating what the component does:
        $myStep = $this->service->getStepForUser($this->deptHead);
        expect($myStep)->toBe(1)
            ->and($this->request->approval_step)->toBe(2)
            ->and($myStep === $this->request->approval_step)->toBeFalse();
    });

    it('reject at any step terminates the chain immediately', function () {
        $this->service->approve($this->request, $this->deptHead);
        $this->request->refresh();

        $this->service->reject($this->request, $this->hr, 'Denied at HR');
        $this->request->refresh();

        expect($this->request->status)->toBe('rejected')
            ->and($this->request->approval_step)->toBeNull();
    });
});

// ── Department Head chain: HR → CEO/MD ───────────────────────────────────────

describe('department_head approval chain (step 2 → 3)', function () {
    beforeEach(function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));
        $this->service  = app(LeaveService::class);
        $this->hr       = chainUser('hr_admin');
        $this->ceo      = chainUser('super_admin');
        $this->lt       = chainLt();
        $deptHeadUser   = chainUser('department_head');
        $this->emp      = chainEmp($deptHeadUser);
        chainCredit($this->emp, $this->lt);
        // Initial step = 2 (dept head skips step 1)
        $this->request  = chainRequest($this->emp, $this->lt, step: 2);
    });

    it('starts at approval_step 2 — not step 1', function () {
        expect($this->request->approval_step)->toBe(2)
            ->and($this->request->status)->toBe('pending');
    });

    it('getInitialStep returns 2 for department_head', function () {
        $deptHeadUser = chainUser('department_head');
        expect($this->service->getInitialStep($deptHeadUser))->toBe(2);
    });

    it('step 2: hr approves → advances to step 3', function () {
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();

        expect($this->request->approval_step)->toBe(3)
            ->and($this->request->status)->toBe('pending');
    });

    it('step 3: CEO approves → fully approved', function () {
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);
        $this->request->refresh();

        expect($this->request->status)->toBe('approved')
            ->and($this->request->approval_step)->toBeNull();
    });

    it('only 2 approval records are created (no step 1)', function () {
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);

        $approvals = LeaveRequestApproval::where('leave_request_id', $this->request->id)->get();

        expect($approvals->count())->toBe(2)
            ->and($approvals->pluck('step')->toArray())->toBe([2, 3])
            ->and($approvals->pluck('label')->toArray())->toBe(['HR', 'CEO / Medical Director']);
    });

    it('dept head step (step 1) is never recorded', function () {
        $this->service->approve($this->request, $this->hr);
        $this->request->refresh();
        $this->service->approve($this->request, $this->ceo);

        expect(
            LeaveRequestApproval::where('leave_request_id', $this->request->id)
                ->where('step', 1)->exists()
        )->toBeFalse();
    });

    it('hr reject terminates chain without reaching CEO', function () {
        $this->service->reject($this->request, $this->hr, 'Not approved');
        $this->request->refresh();

        expect($this->request->status)->toBe('rejected')
            ->and($this->request->approval_step)->toBeNull();
    });
});

// ── Manager chain: CEO/MD only ────────────────────────────────────────────────

describe('manager approval chain (step 3 only)', function () {
    beforeEach(function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));
        $this->service  = app(LeaveService::class);
        $this->ceo      = chainUser('super_admin');
        $this->md       = chainUser('approver');
        $this->lt       = chainLt();
        $managerUser    = chainUser('manager');
        $this->emp      = chainEmp($managerUser);
        chainCredit($this->emp, $this->lt);
        // Initial step = 3 (manager skips steps 1 and 2)
        $this->request  = chainRequest($this->emp, $this->lt, step: 3);
    });

    it('starts at approval_step 3 — skips steps 1 and 2', function () {
        expect($this->request->approval_step)->toBe(3)
            ->and($this->request->status)->toBe('pending');
    });

    it('getInitialStep returns 3 for manager', function () {
        $managerUser = chainUser('manager');
        expect($this->service->getInitialStep($managerUser))->toBe(3);
    });

    it('CEO approves → fully approved in one step', function () {
        $this->service->approve($this->request, $this->ceo);
        $this->request->refresh();

        expect($this->request->status)->toBe('approved')
            ->and($this->request->approval_step)->toBeNull();
    });

    it('Medical Director can also approve at step 3', function () {
        $this->service->approve($this->request, $this->md);
        $this->request->refresh();

        expect($this->request->status)->toBe('approved')
            ->and($this->request->approved_by)->toBe($this->md->id);
    });

    it('only 1 approval record is created', function () {
        $this->service->approve($this->request, $this->ceo);

        $approvals = LeaveRequestApproval::where('leave_request_id', $this->request->id)->get();

        expect($approvals->count())->toBe(1)
            ->and($approvals->first()->step)->toBe(3)
            ->and($approvals->first()->label)->toBe('CEO / Medical Director');
    });

    it('steps 1 and 2 are never recorded', function () {
        $this->service->approve($this->request, $this->ceo);

        expect(
            LeaveRequestApproval::where('leave_request_id', $this->request->id)
                ->whereIn('step', [1, 2])->exists()
        )->toBeFalse();
    });

    it('deducts credits immediately on CEO approval', function () {
        $this->service->approve($this->request, $this->ceo);

        $credit = LeaveCredit::where('employee_id', $this->emp->id)
            ->where('leave_type_id', $this->lt->id)->first();

        expect((float) $credit->used_credits)->toBe(1.0);
    });

    it('CEO reject terminates immediately', function () {
        $this->service->reject($this->request, $this->ceo, 'Not approved');
        $this->request->refresh();

        expect($this->request->status)->toBe('rejected')
            ->and($this->request->approval_step)->toBeNull();
    });
});
