<?php

use App\Livewire\Admin\EmployeeManager;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

function attemptLogin(string $login, string $password = 'password')
{
    return Volt::test('pages.auth.login')
        ->set('form.employee_code', $login)
        ->set('form.password', $password)
        ->call('login');
}

// ── Signing in ────────────────────────────────────────────────────────────────

describe('signing in', function () {
    it('accepts a username', function () {
        $user = User::factory()->create(['username' => 'maria.santos']);

        attemptLogin('maria.santos')->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    });

    it('treats usernames as case-insensitive', function () {
        $user = User::factory()->create(['username' => 'Maria.Santos']);

        expect($user->fresh()->username)->toBe('maria.santos');

        attemptLogin('MARIA.santos')->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    });

    it('still accepts the employee code after a username is set', function () {
        $user = User::factory()->create(['username' => 'maria.santos']);

        attemptLogin($user->employee_code)->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    });

    it('rejects a wrong password for a username', function () {
        User::factory()->create(['username' => 'maria.santos']);

        attemptLogin('maria.santos', 'wrong-password')->assertHasErrors('form.employee_code');

        $this->assertGuest();
    });

    it('rejects an unknown username', function () {
        attemptLogin('nobody.here')->assertHasErrors('form.employee_code');

        $this->assertGuest();
    });
});

// ── Changing it from My Profile ───────────────────────────────────────────────

describe('profile username form', function () {
    it('is shown on the profile page', function () {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSeeVolt('profile.update-username-form');
    });

    it('lets a user set their username with their current password', function () {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', 'Maria.Santos')
            ->set('current_password', 'password')
            ->call('updateUsername')
            ->assertHasNoErrors()
            ->assertDispatched('username-updated');

        expect($user->fresh()->username)->toBe('maria.santos');
    });

    it('requires the current password', function () {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', 'maria.santos')
            ->set('current_password', 'wrong-password')
            ->call('updateUsername')
            ->assertHasErrors('current_password');

        expect($user->fresh()->username)->toBeNull();
    });

    it('rejects a username someone else has', function () {
        User::factory()->create(['username' => 'maria.santos']);
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', 'MARIA.SANTOS')
            ->set('current_password', 'password')
            ->call('updateUsername')
            ->assertHasErrors('username');
    });

    it('rejects a username that matches another user\'s employee code', function () {
        $other = User::factory()->create();
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', strtolower($other->employee_code))
            ->set('current_password', 'password')
            ->call('updateUsername')
            ->assertHasErrors('username');
    });

    it('rejects invalid characters and lengths', function (string $bad) {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', $bad)
            ->set('current_password', 'password')
            ->call('updateUsername')
            ->assertHasErrors('username');
    })->with(['ab', 'maria santos', 'maria@santos', str_repeat('a', 31)]);

    it('can be cleared to sign in with the employee code only', function () {
        $user = User::factory()->create(['username' => 'maria.santos']);

        Volt::actingAs($user)->test('profile.update-username-form')
            ->set('username', '')
            ->set('current_password', 'password')
            ->call('updateUsername')
            ->assertHasNoErrors();

        expect($user->fresh()->username)->toBeNull();
    });
});

// ── HR setting it from Employees → Account ────────────────────────────────────

describe('HR account modal', function () {
    it('sets the username and leaves the employee code untouched', function () {
        Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
        $hr = User::factory()->create(['role' => 'hr_admin']);
        $hr->syncRoles('hr_admin');

        $user = User::factory()->create();
        $emp  = Employee::create(['user_id' => $user->id, 'emp_code' => $user->employee_code,
            'first_name' => 'Maria', 'last_name' => 'Santos', 'is_active' => true]);

        Livewire::actingAs($hr)->test(EmployeeManager::class)
            ->call('openAccountModal', $emp->id)
            ->set('accountNewUsername', 'Maria.Santos')
            ->call('changeUsername')
            ->assertHasNoErrors();

        $user->refresh();
        expect($user->username)->toBe('maria.santos')
            ->and($user->employee_code)->toBe($emp->emp_code);
    });
});
