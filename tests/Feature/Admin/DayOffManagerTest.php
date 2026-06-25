<?php

use App\Livewire\Admin\DayOffManager;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function doffHr(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'hr_admin']);
    $user->syncRoles('hr_admin');
    return $user;
}

function doffEmp(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "DOF{$n}",
        'first_name' => 'Day',
        'last_name'  => "Off{$n}",
        'department' => 'IT',
        'is_active'  => true,
    ], $attrs));
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('allows hr_admin to access day-offs page', function () {
        $this->actingAs(doffHr())->get('/admin/day-offs')->assertOk();
    });

    it('redirects guests to login', function () {
        $this->get('/admin/day-offs')->assertRedirect('/login');
    });

    it('blocks employee role from day-offs page', function () {
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/admin/day-offs')->assertForbidden();
    });
});

// ── Single day off ────────────────────────────────────────────────────────────

describe('single day off', function () {
    it('creates a single day off for an employee', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('date', '2026-08-10')
            ->set('type', 'rest_day')
            ->set('description', 'Weekly rest day')
            ->call('save');

        expect(DayOff::where('employee_id', $emp->id)->whereDate('date', '2026-08-10')->exists())->toBeTrue();
    });

    it('requires employee and date to save', function () {
        $hr = doffHr();

        // openAdd() pre-fills type='rest_day', so only employee and date are missing
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('save')
            ->assertHasErrors(['modalEmployeeId', 'date']);
    });

    it('rejects an invalid type value', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('date', '2026-08-11')
            ->set('type', 'invalid_type')
            ->call('save')
            ->assertHasErrors('type');
    });

    it('prevents duplicate day off on the same date', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-08-12',
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('date', '2026-08-12')
            ->set('type', 'holiday')
            ->call('save')
            ->assertHasErrors('date');

        expect(DayOff::where('employee_id', $emp->id)->whereDate('date', '2026-08-12')->count())->toBe(1);
    });

    it('can edit an existing day off', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        $dayOff = DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-08-13',
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openEdit', $dayOff->id)
            ->assertSet('type', 'rest_day')
            ->set('type', 'holiday')
            ->set('description', 'National Holiday')
            ->call('save');

        expect($dayOff->fresh()->type)->toBe('holiday')
            ->and($dayOff->fresh()->description)->toBe('National Holiday');
    });

    it('can delete a day off', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        $dayOff = DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-08-14',
            'type'        => 'holiday',
            'created_by'  => $hr->id,
        ]);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('delete', $dayOff->id);

        expect(DayOff::find($dayOff->id))->toBeNull();
    });
});

// ── Recurring day off ─────────────────────────────────────────────────────────

describe('recurring day off', function () {
    it('creates day offs for every Saturday in a date range', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        // Saturdays in Aug 2026: 1, 8, 15, 22, 29
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('mode', 'recurring')
            ->set('type', 'rest_day')
            ->set('selectedDays', [6]) // 6 = Saturday
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->call('save');

        $saturdays = DayOff::where('employee_id', $emp->id)->get();
        expect($saturdays->count())->toBe(5)
            ->and($saturdays->pluck('date')->map->format('Y-m-d')->toArray())
            ->toBe(['2026-08-01', '2026-08-08', '2026-08-15', '2026-08-22', '2026-08-29']);
    });

    it('skips already existing day offs when creating recurring', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        // Pre-create one Saturday
        DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-09-05',
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('mode', 'recurring')
            ->set('type', 'rest_day')
            ->set('selectedDays', [6]) // Saturdays
            ->set('dateFrom', '2026-09-01')
            ->set('dateTo', '2026-09-30')
            ->call('save');

        // Sep Saturdays: 5, 12, 19, 26 — the 5th already exists, so only 3 new
        expect(DayOff::where('employee_id', $emp->id)->count())->toBe(4);
    });

    it('requires selectedDays and date range for recurring mode', function () {
        $hr  = doffHr();
        $emp = doffEmp();

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openAdd')
            ->call('selectEmployee', $emp->id)
            ->set('mode', 'recurring')
            ->set('type', 'rest_day')
            ->set('selectedDays', [])
            ->call('save')
            ->assertHasErrors(['selectedDays', 'dateFrom', 'dateTo']);
    });
});

// ── Bulk assign ───────────────────────────────────────────────────────────────

describe('bulk assign', function () {
    it('assigns day offs to all employees in a department', function () {
        $hr   = doffHr();
        $emp1 = doffEmp(['department' => 'Nursing']);
        $emp2 = doffEmp(['department' => 'Nursing']);
        $emp3 = doffEmp(['department' => 'Finance']); // different dept — should be skipped

        // Saturdays in Aug 2026: 1, 8, 15, 22, 29
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openBulk')
            ->set('bulkDept', 'Nursing')
            ->set('bulkType', 'rest_day')
            ->set('bulkSelectedDays', [6])
            ->set('bulkDateFrom', '2026-08-01')
            ->set('bulkDateTo', '2026-08-31')
            ->call('bulkAssign');

        // Each Nursing employee gets 5 Saturdays
        expect(DayOff::where('employee_id', $emp1->id)->count())->toBe(5);
        expect(DayOff::where('employee_id', $emp2->id)->count())->toBe(5);
        expect(DayOff::where('employee_id', $emp3->id)->count())->toBe(0);
    });

    it('requires department, days and date range for bulk assign', function () {
        $hr = doffHr();

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openBulk')
            ->set('bulkType', '') // clear the pre-filled value
            ->call('bulkAssign')
            ->assertHasErrors(['bulkDept', 'bulkType', 'bulkSelectedDays', 'bulkDateFrom', 'bulkDateTo']);
    });
});

// ── Attendance integration ─────────────────────────────────────────────────────

describe('day-off reflected in attendance', function () {
    it('shows Day-off status in attendance when day off exists', function () {
        $processor = app(AttendanceProcessorService::class);
        $hr        = doffHr();
        $emp       = doffEmp();
        $date      = '2026-08-15';

        DayOff::create([
            'employee_id' => $emp->id,
            'date'        => $date,
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        $result = $processor->processDay($emp, $date);

        expect($result['status'])->toBe('Day-off')
            ->and($result['hours_worked'])->toBe(0.0);
    });

    it('shows Absent when no day off is recorded', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = doffEmp();

        $result = $processor->processDay($emp, '2026-08-16');

        expect($result['status'])->toBe('Absent');
    });

    it('day-off takes priority over approved leave on same date', function () {
        $processor = app(AttendanceProcessorService::class);
        $hr        = doffHr();
        $emp       = doffEmp();
        $date      = '2026-08-17';

        // Both a day-off and an approved leave exist for the same date
        DayOff::create([
            'employee_id' => $emp->id,
            'date'        => $date,
            'type'        => 'holiday',
            'created_by'  => $hr->id,
        ]);

        $lt = \App\Models\LeaveType::create([
            'code' => 'DOF_VL', 'name' => 'Vacation Leave',
            'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true,
        ]);

        \App\Models\LeaveRequest::create([
            'employee_id'   => $emp->id,
            'leave_type_id' => $lt->id,
            'start_date'    => $date,
            'end_date'      => $date,
            'total_days'    => 1,
            'reason'        => 'Test',
            'status'        => 'approved',
            'approval_step' => null,
        ]);

        // Day-off is checked first in processDay()
        $result = $processor->processDay($emp, $date);
        expect($result['status'])->toBe('Day-off');
    });
});
