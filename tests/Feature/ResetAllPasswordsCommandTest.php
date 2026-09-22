<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('sets a new password and clears remember_token on every account', function () {
    $a = User::factory()->create(['password' => Hash::make('old-password-a'), 'remember_token' => 'token-a']);
    $b = User::factory()->create(['password' => Hash::make('old-password-b'), 'remember_token' => 'token-b']);

    $this->artisan('users:reset-all-passwords')
        ->expectsConfirmation('Are you sure you want to continue?', 'yes')
        ->expectsQuestion('Enter the new password', 'brand-new-password')
        ->expectsQuestion('Confirm the new password', 'brand-new-password')
        ->assertExitCode(0);

    $a->refresh();
    $b->refresh();

    expect(Hash::check('brand-new-password', $a->password))->toBeTrue()
        ->and(Hash::check('brand-new-password', $b->password))->toBeTrue()
        ->and(Hash::check('old-password-a', $a->password))->toBeFalse()
        ->and($a->remember_token)->toBeNull()
        ->and($b->remember_token)->toBeNull();
});

test('mismatched confirmation makes no changes', function () {
    $user = User::factory()->create(['password' => Hash::make('untouched-password')]);

    $this->artisan('users:reset-all-passwords')
        ->expectsConfirmation('Are you sure you want to continue?', 'yes')
        ->expectsQuestion('Enter the new password', 'password-one')
        ->expectsQuestion('Confirm the new password', 'password-two')
        ->assertExitCode(1);

    $user->refresh();
    expect(Hash::check('untouched-password', $user->password))->toBeTrue();
});

test('declining the confirmation makes no changes', function () {
    $user = User::factory()->create(['password' => Hash::make('untouched-password')]);

    $this->artisan('users:reset-all-passwords')
        ->expectsConfirmation('Are you sure you want to continue?', 'no')
        ->assertExitCode(0);

    $user->refresh();
    expect(Hash::check('untouched-password', $user->password))->toBeTrue();
});

test('rejects a password shorter than 8 characters', function () {
    $user = User::factory()->create(['password' => Hash::make('untouched-password')]);

    $this->artisan('users:reset-all-passwords')
        ->expectsConfirmation('Are you sure you want to continue?', 'yes')
        ->expectsQuestion('Enter the new password', 'short')
        ->expectsQuestion('Confirm the new password', 'short')
        ->assertExitCode(1);

    $user->refresh();
    expect(Hash::check('untouched-password', $user->password))->toBeTrue();
});
