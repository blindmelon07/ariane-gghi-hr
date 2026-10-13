<?php

use App\Livewire\Admin\TimeCorrectionApprovals;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\SmsLog;
use App\Models\TimeCorrection;
use App\Models\TripTicket;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\TripTicketService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['services.unisms.secret_key' => 'sk_test', 'services.unisms.sender_id' => 'GGHI']);
    Http::fake(['unismsapi.com/*' => Http::response(['message' => ['reference_id' => 'm1']], 201)]);
});

function requester(): array
{
    static $n = 0;
    $n++;
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $user = User::factory()->create(['employee_code' => "RQ{$n}", 'role' => 'employee']);
    $emp  = Employee::create([
        'emp_code' => "RQ{$n}", 'first_name' => "Req{$n}", 'last_name' => 'Test', 'is_active' => true,
        'department' => 'Administration', 'user_id' => $user->id, 'cell_number' => '09171234567',
    ]);

    return [$emp, $user];
}

function approverUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => $role]);
    $u->syncRoles($role);
    return $u;
}

it('tells the employee each time their leave moves to the next approver (app only)', function () {
    [$emp, $user] = requester();
    $lt  = LeaveType::create(['code' => 'RQV', 'name' => 'Vacation Leave', 'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true]);
    $req = LeaveRequest::create([
        'employee_id' => $emp->id, 'leave_type_id' => $lt->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-03',
        'total_days' => 2, 'reason' => 'Trip', 'status' => 'pending', 'approval_step' => 1,
    ]);

    app(LeaveService::class)->approve($req, approverUser('department_head'));

    expect($user->notifications()->sole()->data['message'])
        ->toBe('Your Vacation Leave request (Nov 02 - Nov 03, 2026) was approved by Department Head and is now waiting for HR.');
    expect(SmsLog::count())->toBe(0);   // progress updates don't use SMS
});

it('notifies the employee when a time correction is rejected, in the app and by SMS', function () {
    [$emp, $user] = requester();
    $tc = TimeCorrection::create([
        'employee_id' => $emp->id, 'date' => '2026-10-05', 'am_time_in' => '08:00',
        'reason' => 'Forgot', 'status' => 'pending', 'approval_step' => 2,
    ]);

    Livewire::actingAs(approverUser('hr_admin'))
        ->test(TimeCorrectionApprovals::class)
        ->call('openAction', $tc->id, 'reject')
        ->set('remarks', 'No proof')
        ->call('confirmAction');

    expect($user->notifications()->sole()->data)->toMatchArray([
        'title'   => 'Time correction for Oct 05, 2026 — rejected',
        'message' => 'Your time correction for Oct 05, 2026 has been REJECTED. Reason: No proof',
    ]);
    expect(SmsLog::sole())->type->toBe('request')->content->toContain('REJECTED');
});

it('notifies the employee when a time correction is fully approved', function () {
    [$emp, $user] = requester();
    $tc = TimeCorrection::create([
        'employee_id' => $emp->id, 'date' => '2026-10-06', 'am_time_in' => '08:00',
        'reason' => 'Device error', 'status' => 'pending', 'approval_step' => 3,
    ]);

    Livewire::actingAs(approverUser('approver'))
        ->test(TimeCorrectionApprovals::class)
        ->call('openAction', $tc->id, 'approve')
        ->call('confirmAction');

    expect($user->notifications()->sole()->data['message'])->toBe('Your time correction for Oct 06, 2026 has been APPROVED.');
});

it('follows a trip ticket through every step to the final decision', function () {
    [$emp, $user] = requester();
    $ticket = TripTicket::create([
        'employee_id' => $emp->id, 'department' => 'Administration', 'destination_from' => 'GGHI',
        'destination_to' => 'DOH Regional Office', 'departure_datetime' => '2026-10-20 08:00:00',
        'purpose' => 'Supplies', 'status' => 'pending', 'approval_step' => 1,
    ]);
    $service = app(TripTicketService::class);

    $service->approve($ticket->fresh(), approverUser('department_head'));
    $service->approve($ticket->fresh(), approverUser('hr_admin'));
    $service->approve($ticket->fresh(), approverUser('hr_admin'));

    $messages = $user->notifications()->oldest()->get()->pluck('data.message')->all();
    expect($messages)->toBe([
        'Your trip ticket to DOH Regional Office on Oct 20, 2026 was approved by Immediate Head and is now waiting for HR Officer.',
        'Your trip ticket to DOH Regional Office on Oct 20, 2026 was approved by HR Officer and is now waiting for Fleet.',
        'Your trip ticket to DOH Regional Office on Oct 20, 2026 has been APPROVED.',
    ]);
    expect(SmsLog::count())->toBe(1);   // only the final decision is texted
});
