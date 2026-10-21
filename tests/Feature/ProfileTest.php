<?php

use App\Models\Employee;
use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $component
        ->assertHasErrors('password')
        ->assertNoRedirect();

    $this->assertNotNull($user->fresh());
});

test('employee can update their cellphone number and email', function () {
    $user = User::factory()->create(['employee_code' => 'CELL1', 'email' => null]);
    $employee = Employee::create(['emp_code' => 'CELL1', 'first_name' => 'Cell', 'last_name' => 'Owner', 'is_active' => true]);

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('email', 'cell@example.com')
        ->set('cell_number', '+63 917 123 4567')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSet('cell_number', '09171234567');

    expect($employee->fresh()->cell_number)->toBe('09171234567')
        ->and($user->fresh()->email)->toBe('cell@example.com');
});

test('email and cellphone number are optional', function () {
    $user = User::factory()->create(['employee_code' => 'CELL2']);
    $employee = Employee::create(['emp_code' => 'CELL2', 'first_name' => 'No', 'last_name' => 'Contact', 'is_active' => true, 'cell_number' => '09170000000']);

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('email', '')
        ->set('cell_number', '')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($employee->fresh()->cell_number)->toBeNull()
        ->and($user->fresh()->email)->toBeNull();
});

test('an invalid cellphone number is rejected', function () {
    $user = User::factory()->create(['employee_code' => 'CELL3']);
    $employee = Employee::create(['emp_code' => 'CELL3', 'first_name' => 'Bad', 'last_name' => 'Number', 'is_active' => true, 'cell_number' => '09170000000']);

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('cell_number', '12345')
        ->call('updateProfileInformation')
        ->assertHasErrors('cell_number');

    expect($employee->fresh()->cell_number)->toBe('09170000000');
});
