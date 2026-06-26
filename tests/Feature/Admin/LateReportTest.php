<?php

use App\Livewire\Admin\Reports\LateReport;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function lrUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->syncRoles($role);
    return $user;
}

function lrEmployee(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "LR{$n}",
        'first_name' => 'Late',
        'last_name'  => "Tester{$n}",
        'is_active'  => true,
    ], $attrs));
}

function lrPunch(Employee $emp, string $date, string $time, int $state = 0): void
{
    AttendanceLog::create([
        'emp_code'    => $emp->emp_code,
        'employee_id' => $emp->id,
        'punch_time'  => "{$date} {$time}",
        'punch_date'  => $date,
        'punch_state' => $state,
    ]);
}

// ── Route access ─────────────────────────────────────────────────────────────

test('late report accessible by super_admin', function () {
    $this->actingAs(lrUser('super_admin'))
        ->get(route('admin.reports.late'))
        ->assertOk();
});

test('late report accessible by hr_admin', function () {
    $this->actingAs(lrUser('hr_admin'))
        ->get(route('admin.reports.late'))
        ->assertOk();
});

test('late report blocked for employee role', function () {
    $this->actingAs(lrUser('employee'))
        ->get(route('admin.reports.late'))
        ->assertForbidden();
});

// ── Component renders ─────────────────────────────────────────────────────────

test('late report component renders summary cards and table headers', function () {
    Livewire::actingAs(lrUser('super_admin'))
        ->test(LateReport::class)
        ->assertStatus(200)
        ->assertSeeHtml('Late Incidents')
        ->assertSeeHtml('Employees Affected')
        ->assertSeeHtml('Late (min)')
        ->assertSeeHtml('Time In');
});

test('late report defaults to current month date range', function () {
    $comp = Livewire::actingAs(lrUser('super_admin'))->test(LateReport::class);

    expect($comp->get('dateFrom'))->toBe(now()->startOfMonth()->toDateString())
        ->and($comp->get('dateTo'))->toBe(now()->toDateString());
});

// ── Data filtering — only late rows shown ─────────────────────────────────────

test('late report shows only employees who punched in late', function () {
    $ceo  = lrUser('super_admin');
    $emp1 = lrEmployee();
    $emp2 = lrEmployee();

    $date = now()->subDay()->isWeekend()
        ? now()->subDays(3)->toDateString()
        : now()->subDay()->toDateString();

    // emp1 punches in late (09:30)
    lrPunch($emp1, $date, '09:30:00');
    lrPunch($emp1, $date, '12:00:00', 1);
    lrPunch($emp1, $date, '13:00:00');
    lrPunch($emp1, $date, '17:00:00', 1);

    // emp2 punches in on time (07:55)
    lrPunch($emp2, $date, '07:55:00');
    lrPunch($emp2, $date, '12:00:00', 1);
    lrPunch($emp2, $date, '13:00:00');
    lrPunch($emp2, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    $data = $comp->instance()->reportData();

    // Only emp1 should appear
    expect($data->count())->toBe(1)
        ->and($data->first()['emp_code'])->toBe($emp1->emp_code)
        ->and($data->first()['late_min'])->toBeGreaterThan(0);
});

test('late report excludes on-time employees', function () {
    $ceo = lrUser('super_admin');
    $emp = lrEmployee();

    $date = now()->subDay()->isWeekend()
        ? now()->subDays(3)->toDateString()
        : now()->subDay()->toDateString();

    // On time punch (07:50) — full 4-punch pattern
    lrPunch($emp, $date, '07:50:00');
    lrPunch($emp, $date, '12:00:00', 1);
    lrPunch($emp, $date, '13:00:00');
    lrPunch($emp, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    expect($comp->instance()->reportData()->count())->toBe(0);
});

// ── Summary cards ─────────────────────────────────────────────────────────────

test('late report summary cards reflect correct counts', function () {
    $ceo  = lrUser('super_admin');
    $emp1 = lrEmployee();
    $emp2 = lrEmployee();

    $date1 = now()->startOfMonth()->addDay()->toDateString();
    $date2 = now()->startOfMonth()->addDays(2)->toDateString();

    // Both days skip Sunday
    if (now()->startOfMonth()->addDay()->isSunday()) {
        $date1 = now()->startOfMonth()->addDays(2)->toDateString();
        $date2 = now()->startOfMonth()->addDays(3)->toDateString();
    }

    // emp1 late on date1 (30 min late)
    lrPunch($emp1, $date1, '08:30:00');
    lrPunch($emp1, $date1, '17:00:00', 1);

    // emp2 late on date2 (60 min late)
    lrPunch($emp2, $date2, '09:00:00');
    lrPunch($emp2, $date2, '17:00:00', 1);

    $comp  = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date1,
        'dateTo'   => $date2,
    ]);

    $cards = $comp->instance()->summaryCards();

    expect($cards['total_incidents'])->toBe(2)
        ->and($cards['unique_employees'])->toBe(2)
        ->and($cards['total_late_min'])->toBeGreaterThan(0)
        ->and($cards['worst_late_min'])->toBeGreaterThanOrEqual($cards['total_late_min'] / 2);
});

// ── Filters ───────────────────────────────────────────────────────────────────

test('late report department filter scopes results', function () {
    $ceo  = lrUser('super_admin');
    $emp1 = lrEmployee(['department' => 'Nursing']);
    $emp2 = lrEmployee(['department' => 'Admin']);

    $date = now()->startOfMonth()->addDay()->toDateString();
    if (now()->startOfMonth()->addDay()->isSunday()) {
        $date = now()->startOfMonth()->addDays(2)->toDateString();
    }

    lrPunch($emp1, $date, '09:00:00');
    lrPunch($emp1, $date, '17:00:00', 1);
    lrPunch($emp2, $date, '09:00:00');
    lrPunch($emp2, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    $comp->set('department', 'Nursing');
    $data = $comp->instance()->reportData();

    expect($data->count())->toBe(1)
        ->and($data->first()['department'])->toBe('Nursing');
});

test('late report employee filter narrows to single employee', function () {
    $ceo  = lrUser('super_admin');
    $emp1 = lrEmployee();
    $emp2 = lrEmployee();

    $date = now()->startOfMonth()->addDay()->toDateString();
    if (now()->startOfMonth()->addDay()->isSunday()) {
        $date = now()->startOfMonth()->addDays(2)->toDateString();
    }

    lrPunch($emp1, $date, '09:00:00');
    lrPunch($emp1, $date, '17:00:00', 1);
    lrPunch($emp2, $date, '09:00:00');
    lrPunch($emp2, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    $comp->call('selectEmployee', $emp1->id);
    $data = $comp->instance()->reportData();

    expect($data->count())->toBe(1)
        ->and($data->first()['emp_code'])->toBe($emp1->emp_code);
});

test('clear employee filter removes constraint', function () {
    $ceo = lrUser('super_admin');
    $emp = lrEmployee();

    $date = now()->startOfMonth()->addDay()->toDateString();
    if (now()->startOfMonth()->addDay()->isSunday()) {
        $date = now()->startOfMonth()->addDays(2)->toDateString();
    }

    lrPunch($emp, $date, '09:00:00');
    lrPunch($emp, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    $comp->call('selectEmployee', $emp->id);
    expect($comp->get('employeeId'))->toBe($emp->id);

    $comp->call('clearEmployee');
    expect($comp->get('employeeId'))->toBeNull();
});

// ── Row fields ────────────────────────────────────────────────────────────────

test('late report rows contain all expected keys', function () {
    $ceo = lrUser('super_admin');
    $emp = lrEmployee();

    $date = now()->startOfMonth()->addDay()->toDateString();
    if (now()->startOfMonth()->addDay()->isSunday()) {
        $date = now()->startOfMonth()->addDays(2)->toDateString();
    }

    lrPunch($emp, $date, '09:00:00');
    lrPunch($emp, $date, '17:00:00', 1);

    $comp = Livewire::actingAs($ceo)->test(LateReport::class, [
        'dateFrom' => $date,
        'dateTo'   => $date,
    ]);

    $row = $comp->instance()->reportData()->first();

    expect($row)->toHaveKeys(['emp_code', 'name', 'department', 'date', 'time_in', 'late_min', 'status']);
});

// ── Sidebar ───────────────────────────────────────────────────────────────────

test('super_admin sidebar contains late report link', function () {
    $this->actingAs(lrUser('super_admin'))
        ->get(route('admin.dashboard'))
        ->assertSee('admin/reports/late');
});

test('hr_admin sidebar contains late report link', function () {
    $this->actingAs(lrUser('hr_admin'))
        ->get(route('admin.dashboard'))
        ->assertSee('admin/reports/late');
});
