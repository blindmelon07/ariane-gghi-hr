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

function serviceUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function serviceEmp(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "SVC{$n}",
        'first_name' => 'Svc',
        'last_name'  => "Emp{$n}",
        'is_active'  => true,
    ], $attrs));
}

function serviceLt(): LeaveType
{
    static $n = 0;
    $n++;
    return LeaveType::create([
        'code'              => "SLT{$n}",
        'name'              => "SvcLeave{$n}",
        'max_days_per_year' => 10,
        'is_paid'           => true,
        'requires_approval' => true,
    ]);
}

function serviceRequest(Employee $emp, LeaveType $lt, int $step = 1, string $status = 'pending'): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => now()->addDay()->toDateString(),
        'end_date'      => now()->addDay()->toDateString(),
        'total_days'    => 1,
        'reason'        => 'Test',
        'status'        => $status,
        'approval_step' => $step,
    ]);
}

// ── computeTotalDays ──────────────────────────────────────────────────────────

describe('computeTotalDays', function () {
    it('counts a single weekday as 1', function () {
        $service = app(LeaveService::class);
        // Find next Monday
        $monday = now()->next('Monday')->toDateString();
        expect($service->computeTotalDays($monday, $monday))->toBe(1.0);
    });

    it('counts Mon-Fri as 5 days', function () {
        $service = app(LeaveService::class);
        $monday = now()->next('Monday');
        $friday = $monday->copy()->next('Friday');
        expect($service->computeTotalDays($monday->toDateString(), $friday->toDateString()))->toBe(5.0);
    });

    it('excludes Sunday from a Mon-Sun range', function () {
        $service = app(LeaveService::class);
        $monday = now()->next('Monday');
        $sunday = $monday->copy()->addDays(6);
        // Mon-Sun = 7 days, Sunday excluded = 6
        expect($service->computeTotalDays($monday->toDateString(), $sunday->toDateString()))->toBe(6.0);
    });
});

// ── hasOverlap ────────────────────────────────────────────────────────────────

describe('hasOverlap', function () {
    it('detects overlap with a pending request', function () {
        $service = app(LeaveService::class);
        $emp     = serviceEmp();
        $lt      = serviceLt();
        $req     = serviceRequest($emp, $lt); // status defaults to 'pending'
        $date    = $req->start_date->toDateString(); // read date back from the model

        expect($service->hasOverlap($emp->id, $date, $date))->toBeTrue();
    });

    it('returns false when no overlap exists', function () {
        $service = app(LeaveService::class);
        $emp     = serviceEmp();

        expect($service->hasOverlap($emp->id, now()->addMonths(3)->toDateString(), now()->addMonths(3)->toDateString()))->toBeFalse();
    });

    it('excludes a specific request id from overlap check', function () {
        $service = app(LeaveService::class);
        $emp     = serviceEmp();
        $lt      = serviceLt();
        $req     = serviceRequest($emp, $lt, status: 'pending');
        $date    = $req->start_date->toDateString();

        expect($service->hasOverlap($emp->id, $date, $date, $req->id))->toBeFalse();
    });
});

// ── approve ───────────────────────────────────────────────────────────────────

describe('approve', function () {
    it('advances request from step 1 to step 2', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('manager');
        $emp      = serviceEmp();
        $lt       = serviceLt();
        $request  = serviceRequest($emp, $lt, step: 1);

        $service->approve($request, $approver, 'OK');

        expect($request->fresh()->approval_step)->toBe(2)
            ->and($request->fresh()->status)->toBe('pending');
    });

    it('marks request as approved at final step and records approval', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('approver');
        $emp      = serviceEmp();
        $lt       = serviceLt();
        $request  = serviceRequest($emp, $lt, step: 3);

        LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'year'          => now()->year,
            'total_credits' => 10,
            'used_credits'  => 0,
        ]);

        $service->approve($request, $approver);

        $request->refresh();
        expect($request->status)->toBe('approved')
            ->and($request->approved_by)->toBe($approver->id)
            ->and($request->approval_step)->toBeNull();
    });

    it('deducts leave credits on final approval', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('approver');
        $emp      = serviceEmp();
        $lt       = serviceLt();

        LeaveRequest::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(2)->toDateString(),
            'total_days'    => 2,
            'reason'        => 'Test',
            'status'        => 'pending',
            'approval_step' => 3,
        ]);

        $request = LeaveRequest::latest()->first();

        $credit = LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'year'          => now()->year,
            'total_credits' => 10,
            'used_credits'  => 0,
        ]);

        $service->approve($request, $approver);

        expect((float) $credit->fresh()->used_credits)->toBe(2.0);
    });

    it('creates an approval record', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('manager');
        $emp      = serviceEmp();
        $lt       = serviceLt();
        $request  = serviceRequest($emp, $lt, step: 1);

        $service->approve($request, $approver, 'Looks good');

        expect(LeaveRequestApproval::where('leave_request_id', $request->id)->where('step', 1)->exists())->toBeTrue();
    });
});

// ── reject ────────────────────────────────────────────────────────────────────

describe('reject', function () {
    it('marks request as rejected and terminates chain', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('manager');
        $emp      = serviceEmp();
        $lt       = serviceLt();
        $request  = serviceRequest($emp, $lt, step: 1);

        $service->reject($request, $approver, 'No leave allowed');

        $request->refresh();
        expect($request->status)->toBe('rejected')
            ->and($request->approval_step)->toBeNull();
    });

    it('records the rejection with remarks', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $service  = app(LeaveService::class);
        $approver = serviceUser('manager');
        $emp      = serviceEmp();
        $lt       = serviceLt();
        $request  = serviceRequest($emp, $lt, step: 1);

        $service->reject($request, $approver, 'Peak season');

        $approval = LeaveRequestApproval::where('leave_request_id', $request->id)->first();
        expect($approval->action)->toBe('rejected')
            ->and($approval->remarks)->toBe('Peak season');
    });
});
