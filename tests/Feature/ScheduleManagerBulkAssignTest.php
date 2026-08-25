<?php

use App\Livewire\Admin\ScheduleManager;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function scheduleAdminUser(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $u = User::factory()->create(['role' => 'hr_admin']);
    $u->syncRoles('hr_admin');
    return $u;
}

describe('Bulk Assign department/schedule matching', function () {
    it('finds a schedule template despite case and whitespace differences from the employee department', function () {
        Employee::create([
            'emp_code' => 'ACC-1', 'first_name' => 'Ana', 'last_name' => 'Cruz',
            'is_active' => true, 'department' => 'Accounting',
        ]);

        // Template entered with different casing/whitespace than the employee's department string.
        Schedule::create([
            'name' => 'Accounting Day', 'department' => ' accounting ',
            'time_in' => '08:00:00', 'time_out' => '17:00:00', 'is_active' => true,
        ]);

        Livewire::actingAs(scheduleAdminUser())
            ->test(ScheduleManager::class)
            ->call('openBulk')
            ->set('bulkDept', 'Accounting')
            ->assertSee('Accounting Day');
    });

    it('shows no schedules when nothing matches the selected department at all', function () {
        Employee::create([
            'emp_code' => 'HR-1', 'first_name' => 'Jo', 'last_name' => 'Reyes',
            'is_active' => true, 'department' => 'Human Resources',
        ]);

        Livewire::actingAs(scheduleAdminUser())
            ->test(ScheduleManager::class)
            ->call('openBulk')
            ->set('bulkDept', 'Human Resources')
            ->assertSee('Select schedule');
    });

    it('matches the right department across a mix of departments and templates', function (
        string $empDept, string $schedDept, string $schedName
    ) {
        Employee::create([
            'emp_code' => 'SAMP-' . uniqid(), 'first_name' => 'Sample', 'last_name' => 'Employee',
            'is_active' => true, 'department' => $empDept,
        ]);

        Schedule::create([
            'name' => $schedName, 'department' => $schedDept,
            'time_in' => '08:00:00', 'time_out' => '17:00:00', 'is_active' => true,
        ]);

        Livewire::actingAs(scheduleAdminUser())
            ->test(ScheduleManager::class)
            ->call('openBulk')
            ->set('bulkDept', $empDept)
            ->assertSee($schedName)
            ->set('bulkScheduleId', Schedule::where('name', $schedName)->value('id'))
            ->call('bulkAssign')
            ->assertHasNoErrors();

        expect(\App\Models\EmployeeSchedule::whereHas('employee', fn ($q) => $q->where('department', $empDept))->count())
            ->toBe(1);
    })->with([
        'Information Technology' => ['Information Technology', 'IT Department', 'IT Day Shift'],
        'Nursing (exact match)'  => ['Nursing', 'Nursing', 'Nursing Day 12h'],
        'Accounting (case diff)' => ['Accounting', 'ACCOUNTING', 'Accounting Day'],
        'HR (whitespace diff)'   => ['Human Resources', '  Human Resources  ', 'HR Day Shift'],
    ]);
});
