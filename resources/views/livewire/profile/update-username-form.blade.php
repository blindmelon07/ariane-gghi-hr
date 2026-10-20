<?php

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $username = '';
    public string $current_password = '';

    public function mount(): void
    {
        $this->username = Auth::user()->username ?? '';
    }

    /**
     * Set or clear the username the user signs in with. Their employee code
     * keeps working either way.
     */
    public function updateUsername(): void
    {
        $user = Auth::user();
        $this->username = Str::lower(trim($this->username));

        try {
            $this->validate([
                'username'         => User::usernameRules($user->id, required: false),
                'current_password' => ['required', 'string', 'current_password'],
            ], [
                'username.regex' => 'Use only letters, numbers, dots, dashes and underscores.',
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password');

            throw $e;
        }

        $old = $user->username;
        $user->update(['username' => $this->username ?: null]);

        ActivityLogService::log('username_changed', $this->username
            ? "Changed own username from " . ($old ?? '(none)') . " to {$this->username}"
            : "Removed own username {$old}");

        $this->reset('current_password');
        $this->dispatch('username-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Login Username') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            {{ __('Choose a username to sign in with instead of your employee code. Your employee code') }}
            (<span class="font-mono font-medium">{{ Auth::user()->employee_code }}</span>)
            {{ __('will keep working too.') }}
        </p>
    </header>

    <form wire:submit="updateUsername" class="mt-6 space-y-6">
        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input wire:model.live="username" id="username" name="username" type="text" class="mt-1 block w-full"
                          autocomplete="username" placeholder="e.g. maria.santos" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('3–30 characters: letters, numbers, dots, dashes or underscores. Not case-sensitive. Leave blank to remove it.') }}
            </p>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="username_current_password" :value="__('Current Password')" />
            <x-text-input wire:model.live="current_password" id="username_current_password" name="current_password" type="password"
                          class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="username-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
