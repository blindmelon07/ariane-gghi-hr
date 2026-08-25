<?php

use App\Livewire\Admin\EmployeeManager;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ── Helpers ───────────────────────────────────────────────────────────────────

function hrAdmin(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'hr_admin']);
    $user->syncRoles('hr_admin');
    return $user;
}

function freshEmployee(array $attrs = []): Employee
{
    static $n = 0;
    $n++;
    return Employee::create(array_merge([
        'emp_code'   => "EMP{$n}",
        'first_name' => 'Jane',
        'last_name'  => "Doe{$n}",
        'is_active'  => true,
    ], $attrs));
}

// ── Route access ──────────────────────────────────────────────────────────────

describe('route access', function () {
    it('allows hr_admin to load employees page', function () {
        $this->actingAs(hrAdmin())
            ->get('/admin/employees')
            ->assertOk();
    });

    it('redirects unauthenticated users to login', function () {
        $this->get('/admin/employees')->assertRedirect('/login');
    });

    it('blocks employee role from employees page', function () {
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'employee']);
        $user->syncRoles('employee');
        $this->actingAs($user)->get('/admin/employees')->assertForbidden();
    });
});

// ── Edit modal ────────────────────────────────────────────────────────────────

describe('edit modal', function () {
    it('opens edit modal with correct employee data including cell number', function () {
        $emp = freshEmployee(['cell_number' => '09171234567']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->assertSet('showEdit', true)
            ->assertSet('editFirstName', 'Jane')
            ->assertSet('editCellNumber', '09171234567');
    });

    it('opens edit modal with empty cell number when not set', function () {
        $emp = freshEmployee(['cell_number' => null]);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->assertSet('editCellNumber', '');
    });

    it('cancelEdit closes the modal and clears validation', function () {
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->call('cancelEdit')
            ->assertSet('showEdit', false);
    });
});

// ── Save employee ─────────────────────────────────────────────────────────────

describe('save employee', function () {
    it('saves cell number to database', function () {
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editCellNumber', '09991234567')
            ->call('saveEmployee');

        expect($emp->fresh()->cell_number)->toBe('09991234567');
    });

    it('clears cell number when saved as empty', function () {
        $emp = freshEmployee(['cell_number' => '09171234567']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editCellNumber', '')
            ->call('saveEmployee');

        expect($emp->fresh()->cell_number)->toBeNull();
    });

    it('requires first and last name', function () {
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editFirstName', '')
            ->set('editLastName', '')
            ->call('saveEmployee')
            ->assertHasErrors(['editFirstName', 'editLastName']);
    });

    it('saves updated name and keeps modal closed', function () {
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editFirstName', 'Maria')
            ->set('editLastName', 'Santos')
            ->call('saveEmployee')
            ->assertSet('showEdit', false);

        expect($emp->fresh()->first_name)->toBe('Maria')
            ->and($emp->fresh()->last_name)->toBe('Santos');
    });

    it('saves is_active status', function () {
        $emp = freshEmployee(['is_active' => true]);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editIsActive', false)
            ->call('saveEmployee');

        expect($emp->fresh()->is_active)->toBeFalse();
    });
});

describe('editing emp_code (biometric device ID matching)', function () {
    it('updates emp_code so it can be matched to the device-enrolled ID', function () {
        $emp = freshEmployee(['emp_code' => 'GGHI-001']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmpCode', '42')
            ->call('saveEmployee')
            ->assertHasNoErrors();

        expect($emp->fresh()->emp_code)->toBe('42');
    });

    it('keeps the linked login account employee_code in sync when emp_code changes', function () {
        $user = User::factory()->create(['employee_code' => 'GGHI-002', 'role' => 'employee']);
        $emp  = freshEmployee(['emp_code' => 'GGHI-002', 'user_id' => $user->id]);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmpCode', '17')
            ->call('saveEmployee')
            ->assertHasNoErrors();

        expect($emp->fresh()->emp_code)->toBe('17')
            ->and($user->fresh()->employee_code)->toBe('17');
    });

    it('rejects an emp_code already used by another employee', function () {
        freshEmployee(['emp_code' => 'TAKEN']);
        $emp = freshEmployee(['emp_code' => 'GGHI-003']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmpCode', 'TAKEN')
            ->call('saveEmployee')
            ->assertHasErrors(['editEmpCode']);

        expect($emp->fresh()->emp_code)->toBe('GGHI-003');
    });

    it('rejects an emp_code already used as another user\'s login username', function () {
        User::factory()->create(['employee_code' => 'LOGIN-TAKEN']);
        $emp = freshEmployee(['emp_code' => 'GGHI-004']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editEmpCode', 'LOGIN-TAKEN')
            ->call('saveEmployee')
            ->assertHasErrors(['editEmpCode']);
    });

    it('allows keeping the same emp_code unchanged (no false uniqueness conflict)', function () {
        $emp = freshEmployee(['emp_code' => 'GGHI-005']);

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openEdit', $emp->id)
            ->set('editFirstName', 'Updated')
            ->call('saveEmployee')
            ->assertHasNoErrors();

        expect($emp->fresh()->emp_code)->toBe('GGHI-005');
    });
});

// ── Account creation ──────────────────────────────────────────────────────────

describe('account creation', function () {
    it('creates a user account linked to employee', function () {
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openAccountModal', $emp->id)
            ->set('accountPassword', 'secret123')
            ->set('accountRole', 'employee')
            ->call('createAccount');

        expect(User::where('employee_code', $emp->emp_code)->exists())->toBeTrue();
        expect($emp->fresh()->user_id)->not->toBeNull();
    });

    it('requires password with at least 6 characters', function () {
        $emp = freshEmployee();

        Livewire::actingAs(hrAdmin())
            ->test(EmployeeManager::class)
            ->call('openAccountModal', $emp->id)
            ->set('accountPassword', '123')
            ->call('createAccount')
            ->assertHasErrors('accountPassword');
    });
});
