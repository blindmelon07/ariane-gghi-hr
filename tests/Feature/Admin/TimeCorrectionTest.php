<?php

use App\Livewire\Admin\TimeCorrectionApprovals;
use App\Livewire\Employee\TimeCorrectionForm;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\TimeCorrection;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function tcRole(string $role): void
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
}

function tcUser(string $role): User
{
    tcRole($role);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function tcEmployee(User $user): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'   => "TC{$n}",
        'first_name' => $user->name,
        'last_name'  => 'Staff',
        'is_active'  => true,
        'employee_code' => $user->employee_code ?? "TC{$n}",
    ]);
}

function tcEmpOnly(): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'   => "TCE{$n}",
        'first_name' => 'Test',
        'last_name'  => "Staff{$n}",
        'is_active'  => true,
    ]);
}

function punch(Employee $emp, string $date, string $time, int $state): void
{
    AttendanceLog::create([
        'employee_id' => $emp->id,
        'emp_code'    => $emp->emp_code,
        'punch_time'  => Carbon::parse("{$date} {$time}"),
        'punch_date'  => $date,
        'punch_state' => $state,
        'verify_type' => 1,
        'is_processed'=> false,
    ]);
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('employee can access time correction page', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/time-correction')->assertOk();
    });

    it('hr_admin can access admin time corrections page', function () {
        $this->actingAs(tcUser('hr_admin'))->get('/admin/time-corrections')->assertOk();
    });

    it('manager can access admin time corrections page', function () {
        $this->actingAs(tcUser('manager'))->get('/admin/time-corrections')->assertOk();
    });

    it('guest is redirected to login', function () {
        $this->get('/time-correction')->assertRedirect('/login');
        $this->get('/admin/time-corrections')->assertRedirect('/login');
    });
});

// ── Employee filing ───────────────────────────────────────────────────────────

describe('employee filing time correction', function () {
    it('employee can submit a time correction for a past date', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $emp = Employee::create([
            'emp_code'   => $user->employee_code,
            'first_name' => 'Test',
            'last_name'  => 'Staff',
            'is_active'  => true,
        ]);

        Livewire::actingAs($user)
            ->test(TimeCorrectionForm::class)
            ->set('date', now()->subDay()->toDateString())
            ->set('amTimeIn', '08:00')
            ->set('reason', 'Forgot to tap in the morning')
            ->call('submit');

        expect(TimeCorrection::where('employee_id', $emp->id)->exists())->toBeTrue();
    });

    it('requires date and reason', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');

        Livewire::actingAs($user)
            ->test(TimeCorrectionForm::class)
            ->call('submit')
            ->assertHasErrors(['date', 'reason']);
    });

    it('requires at least one time field to be filled', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        Employee::create(['emp_code' => $user->employee_code, 'first_name' => 'T', 'last_name' => 'S', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(TimeCorrectionForm::class)
            ->set('date', now()->subDay()->toDateString())
            ->set('reason', 'Forgot to tap at all punches')
            ->call('submit')
            ->assertHasErrors('amTimeIn');
    });

    it('cannot file a future date', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');

        Livewire::actingAs($user)
            ->test(TimeCorrectionForm::class)
            ->set('date', now()->addDay()->toDateString())
            ->set('amTimeIn', '08:00')
            ->set('reason', 'Future date test reason here')
            ->call('submit')
            ->assertHasErrors('date');
    });

    it('cannot file duplicate pending correction for same date', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $emp = Employee::create(['emp_code' => $user->employee_code, 'first_name' => 'T', 'last_name' => 'S', 'is_active' => true]);
        $date = now()->subDays(2)->toDateString();

        TimeCorrection::create([
            'employee_id' => $emp->id,
            'date'        => $date,
            'am_time_in'  => '08:00',
            'reason'      => 'First request',
            'status'      => 'pending',
            'approval_step' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(TimeCorrectionForm::class)
            ->set('date', $date)
            ->set('amTimeIn', '08:30')
            ->set('reason', 'Second request for same date')
            ->call('submit')
            ->assertHasErrors('date');
    });
});

// ── Approval step resolution ──────────────────────────────────────────────────

describe('approval step uses same chain as leave', function () {
    it('manager gets step 3 initial step', function () {
        $manager = tcUser('manager');
        expect(app(LeaveService::class)->getInitialStep($manager))->toBe(3);
    });

    it('department_head gets step 2 initial step', function () {
        $dh = tcUser('department_head');
        expect(app(LeaveService::class)->getInitialStep($dh))->toBe(2);
    });

    it('employee gets step 1 initial step', function () {
        tcRole('employee');
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        expect(app(LeaveService::class)->getInitialStep($user))->toBe(1);
    });
});

// ── Admin approvals ───────────────────────────────────────────────────────────

describe('admin approving time corrections', function () {
    it('dept_head approves step-1 correction and advances to step 2', function () {
        $deptHead = tcUser('department_head');
        $emp      = tcEmpOnly();
        $date     = now()->subDays(3)->toDateString();

        $tc = TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $date,
            'am_time_in'    => '08:05',
            'reason'        => 'Device did not register',
            'status'        => 'pending',
            'approval_step' => 1,
        ]);

        Livewire::actingAs($deptHead)
            ->test(TimeCorrectionApprovals::class)
            ->call('openAction', $tc->id, 'approve')
            ->call('confirmAction');

        $tc->refresh();
        expect($tc->approval_step)->toBe(2)
            ->and($tc->status)->toBe('pending');
    });

    it('hr_admin approves step-2 and advances to step 3', function () {
        $hr  = tcUser('hr_admin');
        $emp = tcEmpOnly();

        $tc = TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => now()->subDays(3)->toDateString(),
            'pm_time_out'   => '17:00',
            'reason'        => 'Forgot to tap out',
            'status'        => 'pending',
            'approval_step' => 2,
        ]);

        Livewire::actingAs($hr)
            ->test(TimeCorrectionApprovals::class)
            ->call('openAction', $tc->id, 'approve')
            ->call('confirmAction');

        $tc->refresh();
        expect($tc->approval_step)->toBe(3)
            ->and($tc->status)->toBe('pending');
    });

    it('final approver (step 3) fully approves the correction', function () {
        $ceo = tcUser('super_admin');
        $emp = tcEmpOnly();

        $tc = TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => now()->subDays(3)->toDateString(),
            'am_time_in'    => '08:00',
            'reason'        => 'Device error',
            'status'        => 'pending',
            'approval_step' => 3,
        ]);

        Livewire::actingAs($ceo)
            ->test(TimeCorrectionApprovals::class)
            ->call('openAction', $tc->id, 'approve')
            ->call('confirmAction');

        $tc->refresh();
        expect($tc->status)->toBe('approved')
            ->and($tc->approval_step)->toBeNull()
            ->and($tc->approved_by)->toBe($ceo->id);
    });

    it('reject requires a reason', function () {
        $deptHead = tcUser('department_head');
        $emp      = tcEmpOnly();

        $tc = TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => now()->subDays(3)->toDateString(),
            'am_time_in'    => '08:00',
            'reason'        => 'Device error',
            'status'        => 'pending',
            'approval_step' => 1,
        ]);

        Livewire::actingAs($deptHead)
            ->test(TimeCorrectionApprovals::class)
            ->call('openAction', $tc->id, 'reject')
            ->set('remarks', '')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        $tc->refresh();
        expect($tc->status)->toBe('pending');
    });

    it('reject terminates the chain immediately', function () {
        $deptHead = tcUser('department_head');
        $emp      = tcEmpOnly();

        $tc = TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => now()->subDays(3)->toDateString(),
            'am_time_in'    => '08:00',
            'reason'        => 'Device error',
            'status'        => 'pending',
            'approval_step' => 1,
        ]);

        Livewire::actingAs($deptHead)
            ->test(TimeCorrectionApprovals::class)
            ->call('openAction', $tc->id, 'reject')
            ->set('remarks', 'Invalid — time log exists')
            ->call('confirmAction');

        $tc->refresh();
        expect($tc->status)->toBe('rejected')
            ->and($tc->approval_step)->toBeNull();
    });
});

// ── Attendance integration ────────────────────────────────────────────────────

describe('approved correction reflected in attendance', function () {
    it('adds missing AM time-in from approved correction', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = tcEmpOnly();
        $date      = '2026-07-06'; // Monday

        // Only PM punches exist (forgot to tap AM)
        punch($emp, $date, '13:00:00', 0); // PM In
        punch($emp, $date, '17:00:00', 1); // PM Out

        // Before correction: hours_worked = PM only = 4 hrs
        $before = $processor->processDay($emp, $date);
        expect($before['am_time_in'])->toBeNull()
            ->and($before['hours_worked'])->toBe(4.0);

        // Approved correction adds AM In and AM Out
        TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $date,
            'am_time_in'    => '08:00',
            'am_time_out'   => '12:00',
            'reason'        => 'Forgot to tap AM',
            'status'        => 'approved',
            'approval_step' => null,
        ]);

        $after = $processor->processDay($emp, $date);
        expect($after['am_time_in'])->toBe('08:00 AM')
            ->and($after['hours_worked'])->toBeGreaterThan(4.0)
            ->and($after['status'])->toBe('Present');
    });

    it('corrects PM time-out when employee forgot to punch out', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = tcEmpOnly();
        $date      = '2026-07-07';

        // AM punches only — PM in but no PM out (Incomplete)
        punch($emp, $date, '08:00:00', 0); // AM In
        punch($emp, $date, '12:00:00', 1); // AM Out
        punch($emp, $date, '13:00:00', 0); // PM In (no PM Out)

        $before = $processor->processDay($emp, $date);
        expect($before['status'])->toBe('Incomplete');

        // Correction provides the missing PM Out
        TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $date,
            'pm_time_out'   => '17:00',
            'reason'        => 'Device did not register punch out',
            'status'        => 'approved',
            'approval_step' => null,
        ]);

        $after = $processor->processDay($emp, $date);
        expect($after['pm_time_out'])->toBe('05:00 PM')
            ->and($after['status'])->toBe('Present');
    });

    it('pending correction does NOT affect attendance', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = tcEmpOnly();
        $date      = '2026-07-08';

        TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $date,
            'am_time_in'    => '08:00',
            'reason'        => 'Pending correction',
            'status'        => 'pending',
            'approval_step' => 1,
        ]);

        $result = $processor->processDay($emp, $date);
        expect($result['status'])->toBe('Absent'); // no punches, correction still pending
    });

    it('rejected correction does NOT affect attendance', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = tcEmpOnly();
        $date      = '2026-07-09';

        TimeCorrection::create([
            'employee_id'   => $emp->id,
            'date'          => $date,
            'am_time_in'    => '08:00',
            'reason'        => 'Rejected correction',
            'status'        => 'rejected',
            'approval_step' => null,
        ]);

        $result = $processor->processDay($emp, $date);
        expect($result['status'])->toBe('Absent');
    });
});
