<?php

use App\Livewire\Employee\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function ltdRole(string $name): void
{
    Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

test('computeTotalDays excludes both Sunday and the employee weekday_off', function () {
    $service = app(LeaveService::class);

    // Mon Sep 7 -> Mon Sep 14, 2026: spans Saturday Sep 12 and Sunday Sep 13
    $withoutWeekdayOff = $service->computeTotalDays('2026-09-07', '2026-09-14');
    $withSaturdayOff   = $service->computeTotalDays('2026-09-07', '2026-09-14', 6); // 6 = Saturday

    expect($withoutWeekdayOff)->toEqual(7.0);
    expect($withSaturdayOff)->toEqual(6.0);
});

test('filing vacation leave charges credits correctly around the employee weekday_off', function () {
    ltdRole('employee');
    ltdRole('hr_admin');

    $user = User::factory()->create(['role' => 'employee', 'employee_code' => 'LTD1']);
    $user->syncRoles('employee');

    $employee = Employee::create([
        'emp_code'    => 'LTD1',
        'first_name'  => 'Weekday',
        'last_name'   => 'OffTester',
        'is_active'   => true,
        'weekday_off' => 6, // Saturday
    ]);

    $vl = LeaveType::firstOrCreate(['code' => 'VL'], ['name' => 'Vacation Leave', 'max_days_per_year' => 15]);

    LeaveCredit::create([
        'employee_id'   => $employee->id,
        'leave_type_id' => $vl->id,
        'year'          => 2026,
        'total_credits' => 10,
        'used_credits'  => 0,
    ]);

    $component = Livewire::actingAs($user)->test(LeaveRequestForm::class)
        ->set('leave_type_id', $vl->id)
        ->set('start_date', '2026-09-07')
        ->set('end_date', '2026-09-14')
        ->set('reason', 'Family trip spanning my Saturday off');

    expect($component->get('totalDays'))->toEqual(6.0);

    $component->call('submit')->assertHasNoErrors();

    $leaveRequest = LeaveRequest::where('employee_id', $employee->id)->first();
    expect((float) $leaveRequest->total_days)->toEqual(6.0);
});

test('half-day leave stores 0.5 total_days, not a full day', function () {
    ltdRole('employee');
    ltdRole('hr_admin');

    $user = User::factory()->create(['role' => 'employee', 'employee_code' => 'LTD2']);
    $user->syncRoles('employee');

    $employee = Employee::create([
        'emp_code'   => 'LTD2',
        'first_name' => 'Half',
        'last_name'  => 'DayTester',
        'is_active'  => true,
    ]);

    $vl = LeaveType::firstOrCreate(['code' => 'VL'], ['name' => 'Vacation Leave', 'max_days_per_year' => 15]);

    LeaveCredit::create([
        'employee_id'   => $employee->id,
        'leave_type_id' => $vl->id,
        'year'          => 2026,
        'total_credits' => 10,
        'used_credits'  => 0,
    ]);

    $component = Livewire::actingAs($user)->test(LeaveRequestForm::class)
        ->set('leave_type_id', $vl->id)
        ->set('is_half_day', true)
        ->set('start_date', '2026-09-08')
        ->set('reason', 'Half-day personal errand today');

    $component->call('submit')->assertHasNoErrors();

    $leaveRequest = LeaveRequest::where('employee_id', $employee->id)->first();
    expect((float) $leaveRequest->total_days)->toEqual(0.5);
});
