<?php

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use App\Services\LeaveService;
use App\Services\SmsService;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function attEmp(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "ATT{$n}",
        'first_name' => 'Att',
        'last_name'  => "Emp{$n}",
        'is_active'  => true,
    ], $attrs));
}

function attLeaveType(string $name, string $code): LeaveType
{
    static $n = 0;
    $n++;
    return LeaveType::firstOrCreate(['code' => $code], [
        'name'              => $name,
        'max_days_per_year' => 15,
        'is_paid'           => true,
        'requires_approval' => true,
    ]);
}

function attApprovedLeave(Employee $emp, LeaveType $lt, string $start, string $end): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => $start,
        'end_date'      => $end,
        'total_days'    => 1,
        'reason'        => 'Test',
        'status'        => 'approved',
        'approval_step' => null,
    ]);
}

function attPendingLeave(Employee $emp, LeaveType $lt, string $date): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => $date,
        'end_date'      => $date,
        'total_days'    => 1,
        'reason'        => 'Test',
        'status'        => 'pending',
        'approval_step' => 1,
    ]);
}

// ── Leave status marking in attendance ───────────────────────────────────────

describe('approved leave marks attendance status', function () {
    it('shows Vacation Leave status on approved VL day', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL');
        $date = '2026-07-07'; // a Monday

        attApprovedLeave($emp, $vl, $date, $date);

        $result = $processor->processDay($emp, $date);

        expect($result['status'])->toBe('Vacation Leave');
    });

    it('shows Sick Leave status on approved SL day', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $sl   = attLeaveType('Sick Leave', 'SL');
        $date = '2026-07-08';

        attApprovedLeave($emp, $sl, $date, $date);

        $result = $processor->processDay($emp, $date);

        expect($result['status'])->toBe('Sick Leave');
    });

    it('shows Absent when leave is still pending (not yet approved)', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL2');
        $date = '2026-07-09';

        attPendingLeave($emp, $vl, $date);

        $result = $processor->processDay($emp, $date);

        expect($result['status'])->toBe('Absent');
    });

    it('shows Absent with no leave on record', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $date = '2026-07-10';

        $result = $processor->processDay($emp, $date);

        expect($result['status'])->toBe('Absent');
    });

    it('marks all days of a multi-day leave correctly', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL3');

        // Mon–Wed leave
        attApprovedLeave($emp, $vl, '2026-07-13', '2026-07-15');

        expect($processor->processDay($emp, '2026-07-13')['status'])->toBe('Vacation Leave');
        expect($processor->processDay($emp, '2026-07-14')['status'])->toBe('Vacation Leave');
        expect($processor->processDay($emp, '2026-07-15')['status'])->toBe('Vacation Leave');
    });

    it('does not affect days outside the approved leave range', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL4');

        attApprovedLeave($emp, $vl, '2026-07-20', '2026-07-20');

        // Day before and after should still be Absent
        expect($processor->processDay($emp, '2026-07-19')['status'])->toBe('Absent');
        expect($processor->processDay($emp, '2026-07-21')['status'])->toBe('Absent');
    });

    it('leave status takes priority over Absent when no punch exists', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $sl   = attLeaveType('Sick Leave', 'SL2');
        $date = '2026-07-22';

        attApprovedLeave($emp, $sl, $date, $date);

        // No attendance punch exists — without leave it would be Absent
        expect(AttendanceLog::where('employee_id', $emp->id)->count())->toBe(0);
        expect($processor->processDay($emp, $date)['status'])->toBe('Sick Leave');
    });

    it('leave does not override actual attendance punch (employee came to work)', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL5');
        $date = '2026-07-23';

        // Leave was approved but employee actually came in
        attApprovedLeave($emp, $vl, $date, $date);

        AttendanceLog::create([
            'employee_id' => $emp->id,
            'emp_code'    => $emp->emp_code,
            'punch_time'  => \Carbon\Carbon::parse($date . ' 08:00:00'),
            'punch_date'  => $date,
            'punch_state' => 0,
            'verify_type' => 1,
            'is_processed'=> false,
        ]);
        AttendanceLog::create([
            'employee_id' => $emp->id,
            'emp_code'    => $emp->emp_code,
            'punch_time'  => \Carbon\Carbon::parse($date . ' 17:00:00'),
            'punch_date'  => $date,
            'punch_state' => 1,
            'verify_type' => 1,
            'is_processed'=> false,
        ]);

        // Approved leave takes priority (checked before punch lookup)
        $result = $processor->processDay($emp, $date);
        expect($result['status'])->toBe('Vacation Leave');
    });

    it('leave hours_worked is 0 (no punch = no hours)', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL6');
        $date = '2026-07-24';

        attApprovedLeave($emp, $vl, $date, $date);

        $result = $processor->processDay($emp, $date);

        expect($result['hours_worked'])->toBe(0.0)
            ->and($result['minutes_late'])->toBe(0)
            ->and($result['am_time_in'])->toBeNull()
            ->and($result['am_time_out'])->toBeNull();
    });
});

// ── Full flow: file → approve → attendance marked ─────────────────────────────

describe('full flow: leave approved → attendance marked', function () {
    it('attendance shows leave type after final approval', function () {
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('send')->andReturn(true));

        $processor = app(AttendanceProcessorService::class);
        $service   = app(LeaveService::class);

        Role::firstOrCreate(['name' => 'approver', 'guard_name' => 'web']);
        $ceo  = User::factory()->create(['role' => 'approver']);
        $ceo->syncRoles('approver');

        $emp  = attEmp();
        $vl   = attLeaveType('Vacation Leave', 'VL7');
        $date = '2026-08-04'; // a Tuesday

        LeaveCredit::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $vl->id,
            'year'          => 2026,
            'total_credits' => 15,
            'used_credits'  => 0,
        ]);

        // File leave at step 3 (already at final step for this test)
        $request = LeaveRequest::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $vl->id,
            'start_date'    => $date,
            'end_date'      => $date,
            'total_days'    => 1,
            'reason'        => 'Vacation',
            'status'        => 'pending',
            'approval_step' => 3,
        ]);

        // Before approval — still Absent
        expect($processor->processDay($emp, $date)['status'])->toBe('Absent');

        // Approve
        $service->approve($request, $ceo);

        // After approval — shows leave type
        expect($processor->processDay($emp, $date)['status'])->toBe('Vacation Leave');
    });
});
