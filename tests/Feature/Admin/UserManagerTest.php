<?php

use App\Livewire\Admin\UserManager;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (array_keys(UserManager::ROLES) as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

function accountAs(string $role): User
{
    $u = User::factory()->create(['role' => $role]);
    $u->syncRoles($role);
    return $u;
}

it('lets HR admins and super admins open User Accounts, but not employees', function () {
    $this->actingAs(accountAs('hr_admin'))->get(route('admin.accounts'))->assertOk();
    $this->actingAs(accountAs('super_admin'))->get(route('admin.accounts'))->assertOk();
    $this->actingAs(accountAs('employee'))->get(route('admin.accounts'))->assertForbidden();
});

it('creates an account from a picked employee and links it', function () {
    $emp = Employee::create(['emp_code' => '42', 'first_name' => 'Ana', 'last_name' => 'Cruz', 'full_name' => 'Ana Cruz', 'is_active' => true]);

    Livewire::actingAs(accountAs('hr_admin'))
        ->test(UserManager::class)
        ->call('openAdd')
        ->assertSee('Ana Cruz')
        ->call('pickEmployee', $emp->id)
        ->assertSet('name', 'Ana Cruz')
        ->assertSet('employeeCode', '42')
        ->set('password', 'secret123')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('employee_code', '42')->sole();
    expect($emp->fresh()->user_id)->toBe($user->id)
        ->and($user->role)->toBe('employee')
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

it('hides employees who already have an account from the picker', function () {
    $has = User::factory()->create(['employee_code' => '50']);
    Employee::create(['emp_code' => '50', 'first_name' => 'Has', 'last_name' => 'Account', 'is_active' => true, 'user_id' => $has->id]);
    Employee::create(['emp_code' => '51', 'first_name' => 'Needs', 'last_name' => 'Account', 'is_active' => true]);

    $codes = Livewire::actingAs(accountAs('hr_admin'))->test(UserManager::class)
        ->instance()->availableEmployees->pluck('emp_code')->all();

    expect($codes)->toContain('51')->not->toContain('50');
});

it('links a typed-in code to the matching employee', function () {
    $emp = Employee::create(['emp_code' => '60', 'first_name' => 'Ben', 'last_name' => 'Reyes', 'is_active' => true]);

    Livewire::actingAs(accountAs('hr_admin'))
        ->test(UserManager::class)
        ->call('openAdd')
        ->set('name', 'Ben Reyes')->set('employeeCode', '60')->set('password', 'secret123')
        ->call('save');

    expect($emp->fresh()->user_id)->toBe(User::where('employee_code', '60')->value('id'));
});

it('lets HR admins grant Super Admin to others and alerts existing super admins', function () {
    $super = accountAs('super_admin');
    $hr    = accountAs('hr_admin');

    Livewire::actingAs($hr)->test(UserManager::class)
        ->call('openAdd')
        ->set('name', 'New Boss')->set('employeeCode', 'NB1')->set('password', 'secret123')->set('role', 'super_admin')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('employee_code', 'NB1')->value('role'))->toBe('super_admin');
    expect($super->notifications()->sole()->data)->toMatchArray(['title' => 'Super Admin role granted', 'type' => 'security']);
});

it('stops HR admins from changing their own role', function () {
    $hr = accountAs('hr_admin');

    Livewire::actingAs($hr)->test(UserManager::class)
        ->call('openEdit', $hr->id)
        ->assertSet('role', 'hr_admin')
        ->set('role', 'super_admin')
        ->call('save')
        ->assertHasErrors('role');

    expect($hr->fresh()->role)->toBe('hr_admin');
});

it('still stops HR admins from editing, deactivating or deleting existing super admins', function () {
    $super = accountAs('super_admin');

    $hr = Livewire::actingAs(accountAs('hr_admin'))->test(UserManager::class);
    $hr->call('delete', $super->id);
    $hr->call('toggleActive', $super->id);
    $hr->call('openEdit', $super->id)->assertSet('showModal', false);

    expect(User::find($super->id))->not->toBeNull()->is_active->toBeTrue();
});

it('lets a super admin create another super admin', function () {
    Livewire::actingAs(accountAs('super_admin'))
        ->test(UserManager::class)
        ->call('openAdd')
        ->set('name', 'Second Admin')->set('employeeCode', 'SA2')->set('password', 'secret123')->set('role', 'super_admin')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('employee_code', 'SA2')->value('role'))->toBe('super_admin');
});

it('unlinks the employee when its account is deleted', function () {
    $user = User::factory()->create(['employee_code' => '70', 'role' => 'employee']);
    $emp  = Employee::create(['emp_code' => '70', 'first_name' => 'Cy', 'last_name' => 'Go', 'is_active' => true, 'user_id' => $user->id]);

    Livewire::actingAs(accountAs('hr_admin'))->test(UserManager::class)->call('delete', $user->id);

    expect(User::find($user->id))->toBeNull()
        ->and($emp->fresh()->user_id)->toBeNull();
});

it('refuses to delete an account that has approved requests, instead of crashing', function () {
    $approver = accountAs('department_head');
    $emp = Employee::create(['emp_code' => '80', 'first_name' => 'Dee', 'last_name' => 'Lee', 'is_active' => true]);
    $lt  = \App\Models\LeaveType::create(['code' => 'UMX', 'name' => 'VL UMX', 'max_days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true]);
    $req = \App\Models\LeaveRequest::create([
        'employee_id' => $emp->id, 'leave_type_id' => $lt->id, 'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(), 'total_days' => 1, 'reason' => 'x', 'status' => 'pending', 'approval_step' => 1,
    ]);
    app(\App\Services\LeaveService::class)->approve($req, $approver);

    Livewire::actingAs(accountAs('hr_admin'))->test(UserManager::class)->call('delete', $approver->id);

    expect(User::find($approver->id))->not->toBeNull();
});
