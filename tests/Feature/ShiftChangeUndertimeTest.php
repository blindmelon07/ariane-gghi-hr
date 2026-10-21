<?php

use App\Livewire\Admin\ShiftChangeApprovals;
use App\Livewire\Admin\UndertimeApprovals;
use App\Livewire\Employee\ShiftChangeForm;
use App\Livewire\Employee\UndertimeForm;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ShiftChangeRequest;
use App\Models\UndertimeRequest;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Calendar (Oct 2026): Mon 19 is "today".
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-19 09:00:00', 'Asia/Manila'));
});

function scUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function scEmployee(string $code): Employee
{
    return Employee::create([
        'emp_code' => $code, 'first_name' => 'Shift', 'last_name' => $code,
        'is_active' => true, 'employment_type' => 'regular', 'weekday_off' => 6,
    ]);
}

function scShift(string $in, string $out, string $name = 'Mid Shift'): Schedule
{
    return Schedule::create(['name' => $name, 'department' => '', 'time_in' => $in, 'time_out' => $out, 'is_active' => true]);
}

function scPunch(Employee $e, string $date, string $time): void
{
    AttendanceLog::create(['employee_id' => $e->id, 'emp_code' => $e->emp_code, 'punch_date' => $date, 'punch_time' => "{$date} {$time}"]);
}

describe('route access', function () {
    it('employees can open both request pages', function () {
        $user = scUser('employee');
        $this->actingAs($user)->get('/shift-change')->assertOk();
        $this->actingAs($user)->get('/undertime')->assertOk();
    });

    it('hr_admin can open both approval pages, employees cannot', function () {
        $this->actingAs(scUser('hr_admin'))->get('/admin/shift-changes')->assertOk();
        $this->actingAs(scUser('hr_admin'))->get('/admin/undertime')->assertOk();
        $this->actingAs(scUser('employee'))->get('/admin/shift-changes')->assertForbidden();
    });
});

describe('shift change', function () {
    it('files a pending request for a date range', function () {
        $user  = scUser('employee', 'SC1');
        $emp   = scEmployee('SC1');
        $shift = scShift('10:00:00', '19:00:00');

        Livewire::actingAs($user)->test(ShiftChangeForm::class)
            ->set('startDate', '2026-10-20')
            ->set('endDate', '2026-10-22')
            ->set('scheduleId', $shift->id)
            ->set('reason', 'Covering for a co-worker this week')
            ->call('submit')
            ->assertHasNoErrors();

        $req = ShiftChangeRequest::first();
        expect($req->employee_id)->toBe($emp->id)
            ->and($req->status)->toBe('pending')
            ->and($req->approval_step)->toBe(1);
    });

    it('rejects overlapping requests and inactive shifts', function () {
        $user  = scUser('employee', 'SC2');
        $emp   = scEmployee('SC2');
        $shift = scShift('10:00:00', '19:00:00');
        $off   = Schedule::create(['name' => 'Old', 'department' => '', 'time_in' => '06:00', 'time_out' => '15:00', 'is_active' => false]);

        ShiftChangeRequest::create([
            'employee_id' => $emp->id, 'start_date' => '2026-10-21', 'end_date' => '2026-10-21',
            'schedule_id' => $shift->id, 'reason' => 'Earlier request', 'status' => 'pending', 'approval_step' => 1,
        ]);

        Livewire::actingAs($user)->test(ShiftChangeForm::class)
            ->set('startDate', '2026-10-20')->set('endDate', '2026-10-22')
            ->set('scheduleId', $shift->id)->set('reason', 'Overlaps the earlier one')
            ->call('submit')
            ->assertHasErrors('startDate');

        Livewire::actingAs($user)->test(ShiftChangeForm::class)
            ->set('startDate', '2026-10-26')->set('endDate', '2026-10-26')
            ->set('scheduleId', $off->id)->set('reason', 'Inactive shift picked')
            ->call('submit')
            ->assertHasErrors('scheduleId');
    });

    it('once fully approved, attendance is measured against the new shift on those dates only', function () {
        $emp   = scEmployee('SC3');
        $shift = scShift('10:00:00', '19:00:00');
        $req   = ShiftChangeRequest::create([
            'employee_id' => $emp->id, 'start_date' => '2026-10-20', 'end_date' => '2026-10-20',
            'schedule_id' => $shift->id, 'reason' => 'Late shift', 'status' => 'pending', 'approval_step' => 1,
        ]);

        // In 10:00, lunch 12:00–13:00, out 19:00 on both days. Default schedule is 08:00–17:00.
        foreach (['2026-10-20', '2026-10-21'] as $d) {
            foreach (['10:00:00', '12:00:00', '13:00:00', '19:00:00'] as $t) {
                scPunch($emp, $d, $t);
            }
        }

        $processor = app(AttendanceProcessorService::class);
        expect($processor->processDay($emp, '2026-10-20')['minutes_late'])->toBe(120);   // still pending

        Livewire::actingAs(scUser('super_admin'))->test(ShiftChangeApprovals::class)
            ->call('openAction', $req->id, 'approve')
            ->call('confirmAction')
            ->assertHasNoErrors();

        expect($req->fresh()->status)->toBe('approved')
            ->and($processor->processDay($emp, '2026-10-20')['minutes_late'])->toBe(0)
            ->and($processor->processDay($emp, '2026-10-21')['minutes_late'])->toBe(120);

        // Preloaded (report) path agrees
        $processor->preload([$emp->id], '2026-10-19', '2026-10-25');
        expect($processor->processDay($emp, '2026-10-20')['minutes_late'])->toBe(0);
    });

    it('step-1 approval forwards without applying the shift', function () {
        $emp   = scEmployee('SC4');
        $shift = scShift('10:00:00', '19:00:00');
        $req   = ShiftChangeRequest::create([
            'employee_id' => $emp->id, 'start_date' => '2026-10-20', 'end_date' => '2026-10-20',
            'schedule_id' => $shift->id, 'reason' => 'Late shift', 'status' => 'pending', 'approval_step' => 1,
        ]);

        Livewire::actingAs(scUser('department_head'))->test(ShiftChangeApprovals::class)
            ->call('openAction', $req->id, 'approve')
            ->call('confirmAction');

        expect($req->fresh()->status)->toBe('pending')
            ->and($req->fresh()->approval_step)->toBe(2);
    });
});

describe('undertime', function () {
    it('files a request and blocks a second one for the same date', function () {
        $user = scUser('employee', 'UT1');
        $emp  = scEmployee('UT1');

        $form = fn () => Livewire::actingAs($user)->test(UndertimeForm::class)
            ->set('date', '2026-10-20')->set('timeOut', '15:00')
            ->set('reason', 'Medical appointment in the afternoon')
            ->call('submit');

        $form()->assertHasNoErrors();
        $form()->assertHasErrors('date');

        $req = UndertimeRequest::where('employee_id', $emp->id)->sole();
        expect($req->timeOutLabel())->toBe('03:00 PM');
    });

    it('rejecting requires remarks', function () {
        $emp = scEmployee('UT2');
        $req = UndertimeRequest::create([
            'employee_id' => $emp->id, 'date' => '2026-10-20', 'time_out' => '15:00',
            'reason' => 'Appointment', 'status' => 'pending', 'approval_step' => 1,
        ]);

        Livewire::actingAs(scUser('super_admin'))->test(UndertimeApprovals::class)
            ->call('openAction', $req->id, 'reject')
            ->call('confirmAction')
            ->assertHasErrors('remarks');

        expect($req->fresh()->status)->toBe('pending');
    });

    it('approval excuses undertime on record but leaves the minutes in attendance', function () {
        $emp = scEmployee('UT3');
        $req = UndertimeRequest::create([
            'employee_id' => $emp->id, 'date' => '2026-10-19', 'time_out' => '15:00',
            'reason' => 'Appointment', 'status' => 'pending', 'approval_step' => 1,
        ]);
        foreach (['08:00:00', '12:00:00', '13:00:00', '15:00:00'] as $t) {
            scPunch($emp, '2026-10-19', $t);
        }

        Livewire::actingAs(scUser('super_admin'))->test(UndertimeApprovals::class)
            ->call('openAction', $req->id, 'approve')
            ->call('confirmAction');

        expect($req->fresh()->status)->toBe('approved')
            ->and(app(AttendanceProcessorService::class)->processDay($emp, '2026-10-19')['minutes_undertime'])->toBe(120);
    });
});
