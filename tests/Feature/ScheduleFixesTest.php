<?php

use App\Livewire\Admin\ScheduleManager;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AttendanceProcessorService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function sfRole(string $name): void
{
    Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

test('split-shift PM lateness is measured against the schedule time_in_2, not a hardcoded 13:00', function () {
    $employee = Employee::create([
        'emp_code'   => 'SF1',
        'first_name' => 'Split',
        'last_name'  => 'ShiftTester',
        'is_active'  => true,
    ]);

    $schedule = Schedule::create([
        'name'       => 'Split Shift Test',
        'department' => 'Test Dept',
        'time_in'    => '08:00:00',
        'time_out'   => '18:00:00',
        'time_in_2'  => '14:00:00',
        'time_out_2' => '18:00:00',
        'is_active'  => true,
    ]);

    EmployeeSchedule::create([
        'employee_id'    => $employee->id,
        'schedule_id'    => $schedule->id,
        'effective_from' => '2026-09-15',
    ]);

    AttendanceLog::create([
        'employee_id' => $employee->id, 'emp_code' => 'SF1',
        'punch_time' => '2026-09-15 14:05:00', 'punch_date' => '2026-09-15',
        'punch_state' => 1, 'verify_type' => 0, 'is_processed' => false,
    ]);
    AttendanceLog::create([
        'employee_id' => $employee->id, 'emp_code' => 'SF1',
        'punch_time' => '2026-09-15 18:00:00', 'punch_date' => '2026-09-15',
        'punch_state' => 1, 'verify_type' => 0, 'is_processed' => false,
    ]);

    $result = app(AttendanceProcessorService::class)->processDay($employee, '2026-09-15');

    expect($result['minutes_late'])->toBe(5);
});

test('schedule with no time_in_2 still uses the 13:00 PM default (backward compatible)', function () {
    $employee = Employee::create([
        'emp_code'   => 'SF2',
        'first_name' => 'Default',
        'last_name'  => 'ShiftTester',
        'is_active'  => true,
    ]);

    $schedule = Schedule::create([
        'name'       => 'Plain Shift',
        'department' => 'Test Dept',
        'time_in'    => '08:00:00',
        'time_out'   => '17:00:00',
        'is_active'  => true,
    ]);

    EmployeeSchedule::create([
        'employee_id'    => $employee->id,
        'schedule_id'    => $schedule->id,
        'effective_from' => '2026-09-15',
    ]);

    AttendanceLog::create([
        'employee_id' => $employee->id, 'emp_code' => 'SF2',
        'punch_time' => '2026-09-15 13:10:00', 'punch_date' => '2026-09-15',
        'punch_state' => 1, 'verify_type' => 0, 'is_processed' => false,
    ]);
    AttendanceLog::create([
        'employee_id' => $employee->id, 'emp_code' => 'SF2',
        'punch_time' => '2026-09-15 17:00:00', 'punch_date' => '2026-09-15',
        'punch_state' => 1, 'verify_type' => 0, 'is_processed' => false,
    ]);

    $result = app(AttendanceProcessorService::class)->processDay($employee, '2026-09-15');

    expect($result['minutes_late'])->toBe(10);
});

test('deleting a schedule assigned to employees is blocked instead of cascading', function () {
    sfRole('hr_admin');
    $admin = User::factory()->create(['role' => 'hr_admin']);
    $admin->syncRoles('hr_admin');

    $employee = Employee::create([
        'emp_code'   => 'SF3',
        'first_name' => 'InUse',
        'last_name'  => 'ScheduleTester',
        'is_active'  => true,
    ]);

    $schedule = Schedule::create([
        'name'       => 'In-Use Shift',
        'department' => 'Test Dept',
        'time_in'    => '08:00:00',
        'time_out'   => '17:00:00',
        'is_active'  => true,
    ]);

    EmployeeSchedule::create([
        'employee_id'    => $employee->id,
        'schedule_id'    => $schedule->id,
        'effective_from' => '2026-09-15',
    ]);

    Livewire::actingAs($admin)->test(ScheduleManager::class)
        ->call('deleteSchedule', $schedule->id);

    $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    $this->assertDatabaseHas('employee_schedules', ['employee_id' => $employee->id, 'schedule_id' => $schedule->id]);
});

test('deleting an unused schedule still works', function () {
    sfRole('hr_admin');
    $admin = User::factory()->create(['role' => 'hr_admin']);
    $admin->syncRoles('hr_admin');

    $schedule = Schedule::create([
        'name'       => 'Unused Shift',
        'department' => 'Test Dept',
        'time_in'    => '08:00:00',
        'time_out'   => '17:00:00',
        'is_active'  => true,
    ]);

    Livewire::actingAs($admin)->test(ScheduleManager::class)
        ->call('deleteSchedule', $schedule->id);

    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});

test('assign schedule to a specific group of selected employees at once', function () {
    sfRole('hr_admin');
    $admin = User::factory()->create(['role' => 'hr_admin']);
    $admin->syncRoles('hr_admin');

    $nurse1 = Employee::create(['emp_code' => 'GRP1', 'first_name' => 'Nurse', 'last_name' => 'One', 'department' => 'Nursing', 'is_active' => true]);
    $nurse2 = Employee::create(['emp_code' => 'GRP2', 'first_name' => 'Nurse', 'last_name' => 'Two', 'department' => 'Nursing', 'is_active' => true]);
    $other  = Employee::create(['emp_code' => 'GRP3', 'first_name' => 'Other', 'last_name' => 'Dept', 'department' => 'Accounting', 'is_active' => true]);

    $schedule = Schedule::create([
        'name' => 'Nursing Day', 'department' => 'Nursing',
        'time_in' => '08:00:00', 'time_out' => '17:00:00', 'is_active' => true,
    ]);

    Livewire::actingAs($admin)->test(ScheduleManager::class)
        ->call('selectEmployee', $nurse1->id)
        ->call('selectEmployee', $nurse2->id)
        ->set('assignScheduleId', $schedule->id)
        ->set('assignFrom', '2026-10-01')
        ->call('saveAssign')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employee_schedules', ['employee_id' => $nurse1->id, 'schedule_id' => $schedule->id]);
    $this->assertDatabaseHas('employee_schedules', ['employee_id' => $nurse2->id, 'schedule_id' => $schedule->id]);
    $this->assertDatabaseMissing('employee_schedules', ['employee_id' => $other->id, 'schedule_id' => $schedule->id]);
});

test('select all in department adds every active employee in that department', function () {
    sfRole('hr_admin');
    $admin = User::factory()->create(['role' => 'hr_admin']);
    $admin->syncRoles('hr_admin');

    $n1 = Employee::create(['emp_code' => 'D1', 'first_name' => 'A', 'last_name' => 'A', 'department' => 'Nursing', 'is_active' => true]);
    $n2 = Employee::create(['emp_code' => 'D2', 'first_name' => 'B', 'last_name' => 'B', 'department' => 'Nursing', 'is_active' => true]);
    $inactive = Employee::create(['emp_code' => 'D3', 'first_name' => 'C', 'last_name' => 'C', 'department' => 'Nursing', 'is_active' => false]);
    $other = Employee::create(['emp_code' => 'D4', 'first_name' => 'D', 'last_name' => 'D', 'department' => 'Accounting', 'is_active' => true]);

    $schedule = Schedule::create([
        'name' => 'Nursing Day', 'department' => 'Nursing',
        'time_in' => '08:00:00', 'time_out' => '17:00:00', 'is_active' => true,
    ]);

    Livewire::actingAs($admin)->test(ScheduleManager::class)
        ->set('assignEmpDeptFilter', 'Nursing')
        ->call('selectAllInDept')
        ->set('assignScheduleId', $schedule->id)
        ->set('assignFrom', '2026-10-01')
        ->call('saveAssign')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employee_schedules', ['employee_id' => $n1->id, 'schedule_id' => $schedule->id]);
    $this->assertDatabaseHas('employee_schedules', ['employee_id' => $n2->id, 'schedule_id' => $schedule->id]);
    $this->assertDatabaseMissing('employee_schedules', ['employee_id' => $inactive->id, 'schedule_id' => $schedule->id]);
    $this->assertDatabaseMissing('employee_schedules', ['employee_id' => $other->id, 'schedule_id' => $schedule->id]);
});
