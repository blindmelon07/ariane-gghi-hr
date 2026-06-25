<?php

use App\Livewire\Admin\HolidayManager;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function holHr(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'hr_admin']);
    $user->syncRoles('hr_admin');
    return $user;
}

function holEmp(): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'   => "HOL{$n}",
        'first_name' => 'Hol',
        'last_name'  => "Emp{$n}",
        'is_active'  => true,
    ]);
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('allows hr_admin to access holidays page', function () {
        $this->actingAs(holHr())->get('/admin/holidays')->assertOk();
    });

    it('redirects guests to login', function () {
        $this->get('/admin/holidays')->assertRedirect('/login');
    });

    it('blocks employee role from holidays page', function () {
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/admin/holidays')->assertForbidden();
    });
});

// ── Add holiday ───────────────────────────────────────────────────────────────

describe('add holiday', function () {
    it('creates a regular holiday', function () {
        $hr = holHr();

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->set('date', '2026-12-25')
            ->set('name', 'Christmas Day')
            ->set('type', 'regular')
            ->call('save');

        expect(Holiday::where('name', 'Christmas Day')->whereDate('date', '2026-12-25')->exists())->toBeTrue();
    });

    it('creates a special non-working holiday', function () {
        $hr = holHr();

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->set('date', '2026-11-02')
            ->set('name', 'All Souls Day')
            ->set('type', 'special_non_working')
            ->call('save');

        expect(Holiday::where('type', 'special_non_working')->whereDate('date', '2026-11-02')->exists())->toBeTrue();
    });

    it('marks a holiday as recurring', function () {
        $hr = holHr();

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->set('date', '2026-01-01')
            ->set('name', "New Year's Day")
            ->set('type', 'regular')
            ->set('is_recurring', true)
            ->call('save');

        expect(Holiday::where('name', "New Year's Day")->first()->is_recurring)->toBeTrue();
    });

    it('requires date and name to save', function () {
        $hr = holHr();

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->call('save')
            ->assertHasErrors(['date', 'name']);
    });

    it('rejects invalid type', function () {
        $hr = holHr();

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->set('date', '2026-06-12')
            ->set('name', 'Independence Day')
            ->set('type', 'invalid')
            ->call('save')
            ->assertHasErrors('type');
    });

    it('prevents duplicate holiday (same date + name)', function () {
        $hr = holHr();

        Holiday::create(['date' => '2026-06-12', 'name' => 'Independence Day', 'type' => 'regular', 'created_by' => $hr->id]);

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openAdd')
            ->set('date', '2026-06-12')
            ->set('name', 'Independence Day')
            ->set('type', 'regular')
            ->call('save')
            ->assertHasErrors('name');

        expect(Holiday::where('name', 'Independence Day')->count())->toBe(1);
    });
});

// ── Edit & Delete ─────────────────────────────────────────────────────────────

describe('edit and delete', function () {
    it('edits a holiday', function () {
        $hr      = holHr();
        $holiday = Holiday::create(['date' => '2026-08-21', 'name' => 'Ninoy Aquino Day', 'type' => 'special_non_working', 'created_by' => $hr->id]);

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('openEdit', $holiday->id)
            ->assertSet('name', 'Ninoy Aquino Day')
            ->set('type', 'regular')
            ->call('save');

        expect($holiday->fresh()->type)->toBe('regular');
    });

    it('deletes a holiday', function () {
        $hr      = holHr();
        $holiday = Holiday::create(['date' => '2026-11-30', 'name' => 'Bonifacio Day', 'type' => 'regular', 'created_by' => $hr->id]);

        Livewire::actingAs($hr)
            ->test(HolidayManager::class)
            ->call('delete', $holiday->id);

        expect(Holiday::find($holiday->id))->toBeNull();
    });
});

// ── Attendance integration ────────────────────────────────────────────────────

describe('holiday in attendance', function () {
    it('shows holiday name as attendance status on holiday date', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();
        $hr        = holHr();

        Holiday::create(['date' => '2026-12-25', 'name' => 'Christmas Day', 'type' => 'regular', 'created_by' => $hr->id]);

        $result = $processor->processDay($emp, '2026-12-25');

        expect($result['status'])->toBe('Christmas Day')
            ->and($result['hours_worked'])->toBe(0.0);
    });

    it('recurring holiday applies to the same month/day in any year', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();
        $hr        = holHr();

        // Saved as 2026-01-01 but is_recurring = true
        Holiday::create(['date' => '2026-01-01', 'name' => "New Year's Day", 'type' => 'regular', 'is_recurring' => true, 'created_by' => $hr->id]);

        // Check 2027 — same month/day, different year
        $result = $processor->processDay($emp, '2027-01-01');

        expect($result['status'])->toBe("New Year's Day");
    });

    it('non-recurring holiday does NOT apply to other years', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();
        $hr        = holHr();

        Holiday::create(['date' => '2026-06-12', 'name' => 'Independence Day', 'type' => 'regular', 'is_recurring' => false, 'created_by' => $hr->id]);

        // 2027-06-12 should not be a holiday
        $result = $processor->processDay($emp, '2027-06-12');

        expect($result['status'])->toBe('Absent');
    });

    it('holiday takes priority over day-off on the same date', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();
        $hr        = holHr();

        Holiday::create(['date' => '2026-12-25', 'name' => 'Christmas Day', 'type' => 'regular', 'created_by' => $hr->id]);

        \App\Models\DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-12-25',
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        // Holiday is checked first
        $result = $processor->processDay($emp, '2026-12-25');
        expect($result['status'])->toBe('Christmas Day');
    });

    it('holiday takes priority over approved leave on same date', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();
        $hr        = holHr();

        Holiday::create(['date' => '2026-12-25', 'name' => 'Christmas Day', 'type' => 'regular', 'created_by' => $hr->id]);

        $lt = \App\Models\LeaveType::create(['code' => 'HOL_VL', 'name' => 'Vacation Leave', 'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true]);
        \App\Models\LeaveRequest::create([
            'employee_id' => $emp->id, 'leave_type_id' => $lt->id,
            'start_date' => '2026-12-25', 'end_date' => '2026-12-25',
            'total_days' => 1, 'reason' => 'Test', 'status' => 'approved', 'approval_step' => null,
        ]);

        $result = $processor->processDay($emp, '2026-12-25');
        expect($result['status'])->toBe('Christmas Day');
    });

    it('regular work day shows Absent when no punch (no holiday)', function () {
        $processor = app(AttendanceProcessorService::class);
        $emp       = holEmp();

        $result = $processor->processDay($emp, '2026-07-06'); // just a Monday

        expect($result['status'])->toBe('Absent');
    });
});
