<?php

use App\Livewire\Admin\Reports\OvertimeReport;
use App\Livewire\NotificationBell;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Notifications\LeaveRequestFiled;
use App\Notifications\LeaveStatusUpdated;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ──────────────────────────────────────────────────────────────────

function vfRole(string $name): void
{
    Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function vfUser(string $role): User
{
    vfRole($role);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function vfEmployee(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "VFE{$n}",
        'first_name' => 'Test',
        'last_name'  => "Person{$n}",
        'is_active'  => true,
    ], $attrs));
}

function vfLeaveType(?string $code = null, ?string $name = null): LeaveType
{
    static $n = 0;
    $n++;
    return LeaveType::create([
        'code'              => $code ?? "VL{$n}",
        'name'              => $name ?? "Leave Type {$n}",
        'max_days_per_year' => 15,
        'is_paid'           => true,
        'requires_approval' => true,
    ]);
}

function vfLeave(Employee $emp, LeaveType $lt, string $status = 'pending'): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id'   => $emp->id,
        'leave_type_id' => $lt->id,
        'start_date'    => now()->addDay()->toDateString(),
        'end_date'      => now()->addDay()->toDateString(),
        'total_days'    => 1,
        'reason'        => 'Test leave reason',
        'status'        => $status,
        'approval_step' => 1,
    ]);
}

// ── Overtime Report ───────────────────────────────────────────────────────────

test('overtime report accessible by super_admin', function () {
    $user = vfUser('super_admin');
    $this->actingAs($user)->get(route('admin.reports.overtime'))->assertOk();
});

test('overtime report accessible by hr_admin', function () {
    $user = vfUser('hr_admin');
    $this->actingAs($user)->get(route('admin.reports.overtime'))->assertOk();
});

test('overtime report blocked for employee', function () {
    $user = vfUser('employee');
    $this->actingAs($user)->get(route('admin.reports.overtime'))->assertForbidden();
});

test('overtime report component renders with summary cards and table', function () {
    $user = vfUser('super_admin');
    Livewire::actingAs($user)
        ->test(OvertimeReport::class)
        ->assertStatus(200)
        ->assertSeeHtml('Total Requests')
        ->assertSeeHtml('Req. Hours')
        ->assertSeeHtml('Appr. Hours');
});

test('overtime report summary cards count correctly', function () {
    $ceo = vfUser('super_admin');
    $emp = vfEmployee();

    OvertimeRequest::create([
        'employee_id'     => $emp->id,
        'date'            => now()->toDateString(),
        'requested_hours' => 3.0,
        'reason'          => 'Deadline',
        'status'          => 'approved',
        'approved_hours'  => 2.5,
        'approved_by'     => $ceo->id,
    ]);

    OvertimeRequest::create([
        'employee_id'     => $emp->id,
        'date'            => now()->subDay()->toDateString(),
        'requested_hours' => 2.0,
        'reason'          => 'Backlog',
        'status'          => 'pending',
    ]);

    $comp = Livewire::actingAs($ceo)->test(OvertimeReport::class);
    $cards = $comp->instance()->summaryCards();

    expect($cards['total'])->toBe(2)
        ->and($cards['pending'])->toBe(1)
        ->and($cards['approved'])->toBe(1)
        ->and($cards['requested_hours'])->toBe(5.0)
        ->and($cards['approved_hours'])->toBe(2.5);
});

test('overtime report month filter scopes correctly', function () {
    $ceo = vfUser('super_admin');
    $emp = vfEmployee();

    OvertimeRequest::create([
        'employee_id'     => $emp->id,
        'date'            => now()->startOfMonth()->toDateString(),
        'requested_hours' => 2.0,
        'reason'          => 'Test',
        'status'          => 'pending',
    ]);

    $comp = Livewire::actingAs($ceo)->test(OvertimeReport::class);

    $comp->set('month', (string) now()->month);
    expect($comp->instance()->summaryCards()['total'])->toBe(1);

    $comp->set('month', (string) now()->subMonth()->month);
    expect($comp->instance()->summaryCards()['total'])->toBe(0);
});

test('overtime report status filter works', function () {
    $ceo = vfUser('super_admin');
    $emp = vfEmployee();

    OvertimeRequest::create(['employee_id' => $emp->id, 'date' => now()->toDateString(), 'requested_hours' => 2, 'reason' => 'x', 'status' => 'approved', 'approved_hours' => 2, 'approved_by' => $ceo->id]);
    OvertimeRequest::create(['employee_id' => $emp->id, 'date' => now()->toDateString(), 'requested_hours' => 1, 'reason' => 'y', 'status' => 'pending']);

    $comp = Livewire::actingAs($ceo)->test(OvertimeReport::class);

    $comp->set('status', 'approved');
    expect($comp->instance()->summaryCards()['total'])->toBe(1);

    $comp->set('status', 'rejected');
    expect($comp->instance()->summaryCards()['total'])->toBe(0);
});

// ── Dashboard Charts ──────────────────────────────────────────────────────────

test('dashboard weekly attendance chart returns 7 items with correct keys', function () {
    $user  = vfUser('super_admin');
    $comp  = Livewire::actingAs($user)->test(\App\Livewire\Admin\Dashboard::class);
    $chart = $comp->instance()->weeklyAttendanceChart();

    expect($chart)->toHaveCount(7)
        ->and($chart[0])->toHaveKeys(['label', 'present', 'late', 'onTime']);
});

test('dashboard onTime is never negative', function () {
    $user  = vfUser('super_admin');
    $comp  = Livewire::actingAs($user)->test(\App\Livewire\Admin\Dashboard::class);
    $chart = $comp->instance()->weeklyAttendanceChart();

    foreach ($chart as $day) {
        expect($day['onTime'])->toBeGreaterThanOrEqual(0);
    }
});

test('dashboard leave status chart returns array', function () {
    $user  = vfUser('super_admin');
    $comp  = Livewire::actingAs($user)->test(\App\Livewire\Admin\Dashboard::class);

    expect($comp->instance()->leaveStatusChart())->toBeArray();
});

test('dashboard renders chart canvas elements', function () {
    $user = vfUser('super_admin');
    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Dashboard::class)
        ->assertSeeHtml('Weekly Attendance')
        ->assertSeeHtml('Leave Requests')
        ->assertSeeHtml('x-ref="canvas"');
});

test('dashboard page includes chart.js cdn', function () {
    $user = vfUser('super_admin');
    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSee('chart.js');
});

// ── Notification Click ────────────────────────────────────────────────────────

test('LeaveRequestFiled notification data includes url pointing to admin.leave', function () {
    $emp   = vfEmployee();
    $lt    = vfLeaveType('VL', 'Vacation Leave');
    $leave = vfLeave($emp, $lt);

    $data = (new LeaveRequestFiled($leave))->toArray(vfUser('hr_admin'));

    expect($data)->toHaveKey('url')
        ->and($data['url'])->toBe(route('admin.leave'));
});

test('LeaveStatusUpdated notification data includes url pointing to leave.my-requests', function () {
    $emp   = vfEmployee();
    $lt    = vfLeaveType('SL', 'Sick Leave');
    $leave = vfLeave($emp, $lt, 'approved');

    $data = (new LeaveStatusUpdated($leave))->toArray(vfUser('employee'));

    expect($data)->toHaveKey('url')
        ->and($data['url'])->toBe(route('leave.my-requests'));
});

test('markAsRead redirects employee to leave.my-requests', function () {
    $empUser = vfUser('employee');
    $emp     = vfEmployee();
    $lt      = vfLeaveType();
    $leave   = vfLeave($emp, $lt, 'approved');

    $empUser->notify(new LeaveStatusUpdated($leave));
    $notifId = $empUser->notifications()->first()->id;

    Livewire::actingAs($empUser)
        ->test(NotificationBell::class)
        ->call('markAsRead', $notifId)
        ->assertRedirect(route('leave.my-requests'));

    expect($empUser->notifications()->first()->read_at)->not->toBeNull();
});

test('markAsRead redirects hr_admin to admin.leave', function () {
    $hr    = vfUser('hr_admin');
    $emp   = vfEmployee();
    $lt    = vfLeaveType();
    $leave = vfLeave($emp, $lt);

    $hr->notify(new LeaveRequestFiled($leave));
    $notifId = $hr->notifications()->first()->id;

    Livewire::actingAs($hr)
        ->test(NotificationBell::class)
        ->call('markAsRead', $notifId)
        ->assertRedirect(route('admin.leave'));
});

test('markAsRead with nonexistent id does nothing', function () {
    $user = vfUser('employee');

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->call('markAsRead', 'nonexistent-id')
        ->assertNoRedirect();
});

test('notification without stored url falls back to resolveUrl', function () {
    $hr    = vfUser('hr_admin');
    $emp   = vfEmployee();
    $lt    = vfLeaveType();
    $leave = vfLeave($emp, $lt);

    // Notify then manually remove url from data to simulate old notification
    $hr->notify(new LeaveRequestFiled($leave));
    $notif = $hr->notifications()->first();
    $data  = $notif->data;
    unset($data['url']);
    $notif->update(['data' => $data]);

    $notifId = $notif->id;

    Livewire::actingAs($hr)
        ->test(NotificationBell::class)
        ->call('markAsRead', $notifId)
        ->assertRedirect(route('admin.leave'));
});

// ── Sidebar ───────────────────────────────────────────────────────────────────

test('super_admin sidebar contains overtime report link', function () {
    $user = vfUser('super_admin');
    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSee('admin/reports/overtime');
});

test('hr_admin sidebar contains overtime report link', function () {
    $user = vfUser('hr_admin');
    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertSee('admin/reports/overtime');
});
