<?php

use App\Livewire\Admin\DayOffManager;
use App\Livewire\Admin\DayOffSwapApprovals;
use App\Livewire\Employee\DayOffSwapForm;
use App\Models\DayOff;
use App\Models\DayOffSwap;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SalaryDetail;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use App\Services\DayOffSwapService;
use App\Services\LeaveService;
use App\Services\PhilippinePayrollService;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Calendar used throughout (Oct 2026):
 *   Mon 19 (today) · Tue 20 · Wed 21 (weekday_off) · Thu 22 · Fri 23 · Sat 24 · Sun 25
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-19 09:00:00', 'Asia/Manila'));
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function swapUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

/** Regular employee with Wednesday as their weekly rest day, linked to $user when given. */
function swapEmployee(?User $user = null): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'        => $user?->employee_code ?? "SWP{$n}",
        'first_name'      => 'Swap',
        'last_name'       => "Staff{$n}",
        'is_active'       => true,
        'employment_type' => 'regular',
        'weekday_off'     => 3,
    ]);
}

function pendingSwap(Employee $emp, string $original = '2026-10-21', string $new = '2026-10-22', int $step = 1): DayOffSwap
{
    return DayOffSwap::create([
        'employee_id'   => $emp->id,
        'original_date' => $original,
        'new_date'      => $new,
        'reason'        => 'Family event on my rest day',
        'status'        => 'pending',
        'approval_step' => $step,
    ]);
}

function approveSwap(User $approver, DayOffSwap $swap)
{
    return Livewire::actingAs($approver)
        ->test(DayOffSwapApprovals::class)
        ->call('openAction', $swap->id, 'approve')
        ->call('confirmAction');
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('employee can access the swap page', function () {
        $this->actingAs(swapUser('employee'))->get('/day-off-swap')->assertOk();
    });

    it('hr_admin can access the swap approvals page', function () {
        $this->actingAs(swapUser('hr_admin'))->get('/admin/day-off-swaps')->assertOk();
    });

    it('employee cannot access the swap approvals page', function () {
        $this->actingAs(swapUser('employee'))->get('/admin/day-off-swaps')->assertForbidden();
    });
});

// ── Employee filing ───────────────────────────────────────────────────────────

describe('employee filing a swap', function () {
    it('lists upcoming rest days, including scheduled ones without a DayOff record', function () {
        $emp = swapEmployee();

        $restDays = app(DayOffSwapService::class)->upcomingRestDays($emp, 7);

        expect(array_keys($restDays))->toBe(['2026-10-21', '2026-10-25']);
    });

    it('files a pending swap at the first approval step', function () {
        $user = swapUser('employee');
        $emp  = swapEmployee($user);

        Livewire::actingAs($user)
            ->test(DayOffSwapForm::class)
            ->set('originalDate', '2026-10-21')
            ->set('newDate', '2026-10-22')
            ->set('reason', 'Family event on my rest day')
            ->call('submit')
            ->assertHasNoErrors();

        $swap = DayOffSwap::where('employee_id', $emp->id)->first();
        expect($swap)->not->toBeNull()
            ->and($swap->status)->toBe('pending')
            ->and($swap->approval_step)->toBe(1);
    });

    it('rejects an original date that is not a rest day', function () {
        $user = swapUser('employee');
        swapEmployee($user);

        Livewire::actingAs($user)
            ->test(DayOffSwapForm::class)
            ->set('originalDate', '2026-10-22') // Thursday — a working day
            ->set('newDate', '2026-10-23')
            ->set('reason', 'Family event on my rest day')
            ->call('submit')
            ->assertHasErrors('originalDate');

        expect(DayOffSwap::count())->toBe(0);
    });

    it('rejects a new date that is already a rest day', function () {
        $user = swapUser('employee');
        swapEmployee($user);

        Livewire::actingAs($user)
            ->test(DayOffSwapForm::class)
            ->set('originalDate', '2026-10-21')
            ->set('newDate', '2026-10-25') // Sunday
            ->set('reason', 'Family event on my rest day')
            ->call('submit')
            ->assertHasErrors('newDate');
    });

    it('rejects past dates', function () {
        $user = swapUser('employee');
        swapEmployee($user);

        Livewire::actingAs($user)
            ->test(DayOffSwapForm::class)
            ->set('originalDate', '2026-10-14')
            ->set('newDate', '2026-10-15')
            ->set('reason', 'Family event on my rest day')
            ->call('submit')
            ->assertHasErrors(['originalDate', 'newDate']);
    });

    it('rejects a second pending swap touching the same dates', function () {
        $user = swapUser('employee');
        $emp  = swapEmployee($user);
        pendingSwap($emp);

        Livewire::actingAs($user)
            ->test(DayOffSwapForm::class)
            ->set('originalDate', '2026-10-21')
            ->set('newDate', '2026-10-23')
            ->set('reason', 'Changed my mind about the date')
            ->call('submit')
            ->assertHasErrors('originalDate');
    });

    it('employee can cancel their own pending swap', function () {
        $user = swapUser('employee');
        $swap = pendingSwap(swapEmployee($user));

        Livewire::actingAs($user)->test(DayOffSwapForm::class)->call('cancel', $swap->id);

        expect($swap->fresh()->status)->toBe('cancelled');
    });
});

// ── Approval ──────────────────────────────────────────────────────────────────

describe('approving a swap', function () {
    it('step-1 approval forwards to HR without moving the rest day', function () {
        $swap = pendingSwap(swapEmployee());

        approveSwap(swapUser('department_head'), $swap);

        expect($swap->fresh()->approval_step)->toBe(2)
            ->and($swap->fresh()->status)->toBe('pending')
            ->and(DayOff::count())->toBe(0);
    });

    it('final approval of a scheduled rest day creates a DayOff on the new date', function () {
        $emp  = swapEmployee();
        $swap = pendingSwap($emp, step: 3);

        approveSwap(swapUser('super_admin'), $swap);

        $processor = app(AttendanceProcessorService::class);

        expect($swap->fresh()->status)->toBe('approved')
            ->and(DayOff::where('employee_id', $emp->id)->whereDate('date', '2026-10-22')->exists())->toBeTrue()
            ->and($processor->isScheduledRestDay($emp, Carbon::parse('2026-10-21')))->toBeFalse()
            ->and($processor->isScheduledRestDay($emp, Carbon::parse('2026-10-28')))->toBeTrue(); // next Wednesday unaffected
    });

    it('final approval moves an existing DayOff record', function () {
        $emp    = swapEmployee();
        $dayOff = DayOff::create(['employee_id' => $emp->id, 'date' => '2026-10-21', 'type' => 'rest_day', 'is_recurring' => true, 'recurring_day_of_week' => 3]);
        $swap   = pendingSwap($emp, step: 3);

        approveSwap(swapUser('super_admin'), $swap);

        $dayOff->refresh();
        expect($dayOff->date->toDateString())->toBe('2026-10-22')
            ->and($dayOff->description)->toBe('Swapped from Oct 21, 2026')
            ->and(DayOff::where('employee_id', $emp->id)->count())->toBe(1);
    });

    it('blocks final approval when the new date became a rest day meanwhile', function () {
        $emp  = swapEmployee();
        $swap = pendingSwap($emp, step: 3);
        DayOff::create(['employee_id' => $emp->id, 'date' => '2026-10-22', 'type' => 'special']);

        approveSwap(swapUser('super_admin'), $swap)->assertHasErrors('remarks');

        expect($swap->fresh()->status)->toBe('pending');
    });

    it('rejecting requires remarks and leaves the schedule untouched', function () {
        $swap = pendingSwap(swapEmployee());

        Livewire::actingAs(swapUser('department_head'))
            ->test(DayOffSwapApprovals::class)
            ->call('openAction', $swap->id, 'reject')
            ->call('confirmAction')
            ->assertHasErrors('remarks')
            ->set('remarks', 'Short-staffed that day')
            ->call('confirmAction');

        expect($swap->fresh()->status)->toBe('rejected')
            ->and(DayOff::count())->toBe(0);
    });
});

// ── Effects of an approved swap ───────────────────────────────────────────────

describe('approved swap effects', function () {
    it('keeps payroll working days unchanged', function () {
        $emp = swapEmployee();
        SalaryDetail::create([
            'employee_id'  => $emp->id,
            'rate_type'    => 'monthly',
            'basic_salary' => 22000.00,
            'daily_rate'   => 800.00,
            'hourly_rate'  => 100.00,
            'is_active'    => true,
        ]);
        $period = PayrollPeriod::create([
            'name'        => 'Oct 2026 - 2nd Half',
            'cutoff_type' => 'semi_monthly_2',
            'start_date'  => '2026-10-16',
            'end_date'    => '2026-10-31',
            'status'      => 'draft',
        ]);

        $before = app(PhilippinePayrollService::class)->computePayslip($emp, $period)['working_days'];

        $swap = pendingSwap($emp, step: 3);
        approveSwap(swapUser('super_admin'), $swap);

        $after = app(PhilippinePayrollService::class)->computePayslip($emp->fresh(), $period)['working_days'];

        expect($after)->toEqual($before);
    });

    it('counts the swapped-away rest day as a leave day', function () {
        $emp = swapEmployee();
        DayOffSwap::create([
            'employee_id' => $emp->id, 'original_date' => '2026-10-21', 'new_date' => '2026-10-22',
            'reason' => 'Swap', 'status' => 'approved', 'approval_step' => null,
        ]);

        $leave = app(LeaveService::class);

        expect($leave->computeTotalDays('2026-10-20', '2026-10-21', 3))->toEqual(1)
            ->and($leave->computeTotalDays('2026-10-20', '2026-10-21', 3, $emp->id))->toEqual(2);
    });

    it('auto-generate does not recreate the swapped-away rest day', function () {
        $emp = swapEmployee();
        DayOffSwap::create([
            'employee_id' => $emp->id, 'original_date' => '2026-10-21', 'new_date' => '2026-10-22',
            'reason' => 'Swap', 'status' => 'approved', 'approval_step' => null,
        ]);

        Livewire::actingAs(swapUser('hr_admin'))
            ->test(DayOffManager::class)
            ->set('genDateFrom', '2026-10-19')
            ->set('genDateTo', '2026-10-25')
            ->call('generateStandard');

        $dates = DayOff::where('employee_id', $emp->id)->pluck('date')->map->toDateString()->all();
        expect($dates)->toBe(['2026-10-25']);
    });
});
