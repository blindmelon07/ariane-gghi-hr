<?php

use App\Livewire\Admin\LeaveApprovals;
use App\Livewire\Admin\TimeCorrectionApprovals;
use App\Livewire\Admin\TripTicketApprovals;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TimeCorrection;
use App\Models\TripTicket;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function overrideUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => $role]);
    $u->syncRoles($role);
    return $u;
}

function overrideEmployee(): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(['emp_code' => "OVR{$n}", 'first_name' => "Emp{$n}", 'last_name' => 'Test', 'is_active' => true, 'department' => 'Administration']);
}

function overrideLeave(Employee $emp, int $step = 1): LeaveRequest
{
    $lt = LeaveType::create(['code' => 'OV' . $emp->id, 'name' => 'Vacation ' . $emp->id, 'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true]);
    LeaveCredit::create(['employee_id' => $emp->id, 'leave_type_id' => $lt->id, 'year' => now()->addDay()->year, 'total_credits' => 15, 'used_credits' => 0]);

    return LeaveRequest::create([
        'employee_id' => $emp->id, 'leave_type_id' => $lt->id,
        'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
        'total_days' => 1, 'reason' => 'Personal', 'status' => 'pending', 'approval_step' => $step,
    ]);
}

describe('Leave', function () {
    it('lets a super admin see and approve a leave still waiting for the department head', function () {
        $req = overrideLeave(overrideEmployee(), 1);

        Livewire::actingAs(overrideUser('super_admin'))
            ->test(LeaveApprovals::class)
            ->assertSee('Approve (override)')
            ->call('openAction', $req->id, 'approve')
            ->set('remarks', 'Urgent')
            ->call('confirmAction')
            ->assertHasNoErrors();

        $req->refresh();
        expect($req->status)->toBe('approved')
            ->and($req->approval_step)->toBeNull()
            ->and(LeaveCredit::where('employee_id', $req->employee_id)->value('used_credits'))->toEqual(1);

        $labels = $req->approvals()->orderBy('step')->pluck('label', 'step')->all();
        expect($labels)->toBe([
            1 => 'Department Head (Super Admin override)',
            2 => 'HR (Super Admin override)',
            3 => 'CEO / Medical Director',          // the super admin's own step
        ]);
    });

    it('lets a super admin reject at any step and marks it as an override', function () {
        $req = overrideLeave(overrideEmployee(), 2);

        Livewire::actingAs(overrideUser('super_admin'))
            ->test(LeaveApprovals::class)
            ->call('openAction', $req->id, 'reject')
            ->set('remarks', 'Not allowed')
            ->call('confirmAction');

        expect($req->fresh()->status)->toBe('rejected')
            ->and($req->approvals()->sole()->label)->toBe('HR (Super Admin override)');
    });

    it('still blocks other roles from acting outside their step', function () {
        $req = overrideLeave(overrideEmployee(), 1);

        Livewire::actingAs(overrideUser('hr_admin'))
            ->test(LeaveApprovals::class)
            ->call('openAction', $req->id, 'approve')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        expect($req->fresh()->status)->toBe('pending');
    });
});

it('lets a super admin approve a time correction at any step', function () {
    $tc = TimeCorrection::create([
        'employee_id' => overrideEmployee()->id, 'date' => now()->subDay()->toDateString(),
        'am_time_in' => '08:00', 'reason' => 'Forgot to punch', 'status' => 'pending', 'approval_step' => 1,
    ]);

    Livewire::actingAs(overrideUser('super_admin'))
        ->test(TimeCorrectionApprovals::class)
        ->assertSee('Approve (override)')
        ->call('openAction', $tc->id, 'approve')
        ->call('confirmAction')
        ->assertHasNoErrors();

    expect($tc->fresh())->status->toBe('approved')->approval_step->toBeNull();
});

it('lets a super admin approve a trip ticket still at the immediate head', function () {
    $emp    = overrideEmployee();
    $ticket = TripTicket::create([
        'employee_id' => $emp->id, 'department' => 'Administration',
        'destination_from' => 'GGHI', 'destination_to' => 'DOH', 'departure_datetime' => now()->addDay(),
        'purpose' => 'Supplies', 'status' => 'pending', 'approval_step' => 1,
    ]);

    Livewire::actingAs(overrideUser('super_admin'))
        ->test(TripTicketApprovals::class)
        ->assertSee('Approve (override)')
        ->call('openAction', $ticket->id, 'approve')
        ->call('confirmAction')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe('approved')
        ->and($ticket->approvals()->orderBy('step')->pluck('label')->all())->toBe([
            'Immediate Head (Super Admin override)',
            'HR Officer (Super Admin override)',
            'Fleet',
        ]);
});
