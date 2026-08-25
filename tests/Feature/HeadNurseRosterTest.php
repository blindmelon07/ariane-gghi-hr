<?php

use App\Livewire\HeadNurse\RosterManager;
use App\Models\Employee;
use App\Models\NurseDutyRoster;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function headNurseUser(): User
{
    Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => 'head_nurse']);
    $u->syncRoles('head_nurse');
    return $u;
}

function plainEmployeeUser(): User
{
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => 'employee']);
    $u->syncRoles('employee');
    return $u;
}

function nursingStaff(string $name): Employee
{
    return Employee::create([
        'emp_code'   => 'RN-' . uniqid(),
        'first_name' => $name,
        'last_name'  => 'Nurse',
        'is_active'  => true,
        'department' => 'Nursing',
    ]);
}

function dayShiftTemplate(): Schedule
{
    return Schedule::create([
        'name'           => 'Nursing Day 12h',
        'department'     => 'Nursing',
        'time_in'        => '07:00:00',
        'time_out'       => '19:00:00',
        'is_night_shift' => false,
        'is_active'      => true,
    ]);
}

describe('Head nurse duty roster access control', function () {
    it('blocks a plain employee from the roster route', function () {
        $this->actingAs(plainEmployeeUser())
            ->get(route('head-nurse.roster'))
            ->assertForbidden();
    });

    it('allows a head_nurse to view the roster route', function () {
        $this->actingAs(headNurseUser())
            ->get(route('head-nurse.roster'))
            ->assertOk();
    });

    it('routes a head_nurse dashboard to the roster page', function () {
        expect(headNurseUser()->dashboardRoute())->toBe('/head-nurse/roster');
    });
});

describe('Head nurse duty roster component', function () {
    it('only lists active Nursing department employees', function () {
        $nurse   = nursingStaff('Active');
        $other   = Employee::create([
            'emp_code' => 'IT-1', 'first_name' => 'Non', 'last_name' => 'Nurse',
            'is_active' => true, 'department' => 'Information Technology',
        ]);
        $inactive = Employee::create([
            'emp_code' => 'RN-INACTIVE', 'first_name' => 'Retired', 'last_name' => 'Nurse',
            'is_active' => false, 'department' => 'Nursing',
        ]);

        Livewire::actingAs(headNurseUser())
            ->test(RosterManager::class)
            ->assertSee('Active')
            ->assertDontSee('Non Nurse')
            ->assertDontSee('Retired');
    });

    it('assigns a shift to a nurse for a given date', function () {
        $nurse    = nursingStaff('Cruz');
        $schedule = dayShiftTemplate();
        $date     = now()->addDay()->toDateString();

        Livewire::actingAs(headNurseUser())
            ->test(RosterManager::class)
            ->call('openCell', $nurse->id, $date)
            ->set('selectedScheduleId', $schedule->id)
            ->call('saveCell');

        expect(NurseDutyRoster::where('employee_id', $nurse->id)->whereDate('date', $date)->first())
            ->schedule_id->toBe($schedule->id);
    });

    it('marks a nurse explicitly OFF (schedule_id null) distinct from unscheduled', function () {
        $nurse = nursingStaff('Reyes');
        $date  = now()->addDays(2)->toDateString();

        Livewire::actingAs(headNurseUser())
            ->test(RosterManager::class)
            ->call('openCell', $nurse->id, $date)
            ->set('selectedScheduleId', null)
            ->call('saveCell');

        $entry = NurseDutyRoster::where('employee_id', $nurse->id)->whereDate('date', $date)->first();

        expect($entry)->not->toBeNull()
            ->and($entry->schedule_id)->toBeNull();
    });

    it('clears a roster entry back to unscheduled', function () {
        $nurse    = nursingStaff('Santos');
        $schedule = dayShiftTemplate();
        $date     = now()->addDays(3)->toDateString();

        NurseDutyRoster::create(['employee_id' => $nurse->id, 'schedule_id' => $schedule->id, 'date' => $date]);

        Livewire::actingAs(headNurseUser())
            ->test(RosterManager::class)
            ->call('openCell', $nurse->id, $date)
            ->call('clearCell');

        expect(NurseDutyRoster::where('employee_id', $nurse->id)->whereDate('date', $date)->exists())->toBeFalse();
    });
});
