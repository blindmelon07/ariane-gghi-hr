<?php

use App\Livewire\Admin\DayOffManager;
use App\Livewire\Admin\EmployeeManager;
use App\Models\DayOff;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function schedHr(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'hr_admin']);
    $user->syncRoles('hr_admin');
    return $user;
}

function schedEmp(string $type = 'regular', ?int $weekdayOff = 6): Employee
{
    static $n = 0;
    $n++;
    return Employee::create([
        'emp_code'        => "SCH{$n}",
        'first_name'      => 'Sched',
        'last_name'       => "Emp{$n}",
        'is_active'       => true,
        'employment_type' => $type,
        'weekday_off'     => $weekdayOff,
    ]);
}

// ── weekday_off defaults ──────────────────────────────────────────────────────

describe('weekday_off defaults on employee', function () {
    it('regular employee defaults to Saturday (6)', function () {
        $emp = schedEmp('regular', 6);
        expect($emp->weekday_off)->toBe(6);
    });

    it('probationary employee has null weekday_off', function () {
        $emp = schedEmp('probationary', null);
        expect($emp->weekday_off)->toBeNull();
    });

    it('regular employee can be set to Monday (1)', function () {
        $emp = schedEmp('regular', 1);
        expect($emp->weekday_off)->toBe(1);
    });
});

// ── EmployeeManager: save weekday_off ─────────────────────────────────────────

describe('EmployeeManager weekday_off', function () {
    it('saves Saturday (6) as weekday_off for regular employee', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        Livewire::actingAs($hr)
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->assertSet('editWeekdayOff', 6)
            ->set('editWeekdayOff', 1)
            ->call('saveEmployee');

        expect($emp->fresh()->weekday_off)->toBe(1);
    });

    it('clears weekday_off to null when switching to probationary', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        Livewire::actingAs($hr)
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmploymentType', 'probationary')
            ->call('saveEmployee');

        expect($emp->fresh()->weekday_off)->toBeNull();
    });

    it('updatedEditEmploymentType auto-clears weekday_off for probationary', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        Livewire::actingAs($hr)
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmploymentType', 'probationary')
            ->assertSet('editWeekdayOff', null);
    });

    it('updatedEditEmploymentType restores Saturday when switching back to regular', function () {
        $hr  = schedHr();
        $emp = schedEmp('probationary', null);

        Livewire::actingAs($hr)
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->assertSet('editWeekdayOff', null)
            ->set('editEmploymentType', 'regular')
            ->assertSet('editWeekdayOff', 6); // auto-set to Saturday
    });

    it('rejects invalid weekday_off values', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        Livewire::actingAs($hr)
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editWeekdayOff', 3) // Wednesday — not allowed
            ->call('saveEmployee')
            ->assertHasErrors('editWeekdayOff');
    });
});

// ── generateStandard ──────────────────────────────────────────────────────────

describe('generateStandard day offs', function () {
    it('requires date range to generate', function () {
        $hr = schedHr();

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '')
            ->set('genDateTo', '')
            ->call('generateStandard')
            ->assertHasErrors(['genDateFrom', 'genDateTo']);
    });

    it('regular (Saturday off) gets Saturday + Sunday day offs', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6); // Saturday rest day

        // Week of Aug 3–9 2026: Mon=3, Tue=4, Wed=5, Thu=6, Fri=7, Sat=8, Sun=9
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-03')
            ->set('genDateTo', '2026-08-09')
            ->call('generateStandard');

        $dates = DayOff::where('employee_id', $emp->id)
            ->get()
            ->map(fn ($d) => $d->date->format('Y-m-d'))
            ->sort()->values()->toArray();

        expect($dates)->toBe(['2026-08-08', '2026-08-09']); // Sat + Sun
    });

    it('regular (Monday off) gets Monday + Sunday day offs', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 1); // Monday rest day

        // Week of Aug 3–9 2026
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-03')
            ->set('genDateTo', '2026-08-09')
            ->call('generateStandard');

        $dates = DayOff::where('employee_id', $emp->id)
            ->get()
            ->map(fn ($d) => $d->date->format('Y-m-d'))
            ->sort()->values()->toArray();

        expect($dates)->toBe(['2026-08-03', '2026-08-09']); // Mon + Sun
    });

    it('probationary employee gets Sunday only', function () {
        $hr  = schedHr();
        $emp = schedEmp('probationary', null);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-03')
            ->set('genDateTo', '2026-08-09')
            ->call('generateStandard');

        $dates = DayOff::where('employee_id', $emp->id)
            ->get()
            ->map(fn ($d) => $d->date->format('Y-m-d'))
            ->toArray();

        expect($dates)->toBe(['2026-08-09']); // Sunday only
        expect(count($dates))->toBe(1);
    });

    it('processes all employee types in one run', function () {
        $hr       = schedHr();
        $regular  = schedEmp('regular', 6);    // Sat+Sun
        $monOff   = schedEmp('regular', 1);    // Mon+Sun
        $probi    = schedEmp('probationary', null); // Sun only

        // Aug 3–9 2026
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-03')
            ->set('genDateTo', '2026-08-09')
            ->call('generateStandard');

        expect(DayOff::where('employee_id', $regular->id)->count())->toBe(2);  // Sat+Sun
        expect(DayOff::where('employee_id', $monOff->id)->count())->toBe(2);   // Mon+Sun
        expect(DayOff::where('employee_id', $probi->id)->count())->toBe(1);    // Sun
    });

    it('skips days already assigned (no duplicates)', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        // Pre-assign Saturday
        DayOff::create([
            'employee_id' => $emp->id,
            'date'        => '2026-08-08',
            'type'        => 'rest_day',
            'created_by'  => $hr->id,
        ]);

        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-03')
            ->set('genDateTo', '2026-08-09')
            ->call('generateStandard');

        // Should have exactly 2 (Sat was skipped, Sun was added)
        expect(DayOff::where('employee_id', $emp->id)->count())->toBe(2);
    });

    it('regular (Sat off) gets 2 days off per week over a month', function () {
        $hr  = schedHr();
        $emp = schedEmp('regular', 6);

        // Aug 2026 has 5 Saturdays (1,8,15,22,29) and 5 Sundays (2,9,16,23,30) = 10
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-01')
            ->set('genDateTo', '2026-08-31')
            ->call('generateStandard');

        expect(DayOff::where('employee_id', $emp->id)->count())->toBe(10);
    });

    it('probationary gets 1 day off per week (Sunday only) over a month', function () {
        $hr  = schedHr();
        $emp = schedEmp('probationary', null);

        // Aug 2026 has 5 Sundays
        Livewire::actingAs($hr)
            ->test(DayOffManager::class)
            ->call('openGenerate')
            ->set('genDateFrom', '2026-08-01')
            ->set('genDateTo', '2026-08-31')
            ->call('generateStandard');

        expect(DayOff::where('employee_id', $emp->id)->count())->toBe(5);
    });
});
