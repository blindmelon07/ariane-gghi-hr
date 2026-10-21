<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $cell_number = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name ?? '';
        $this->email = Auth::user()->email ?? '';
        $this->cell_number = $this->employee()?->cell_number ?? '';
    }

    /**
     * The employee record linked to this login, if any (admins may have none).
     */
    public function employee(): ?Employee
    {
        $code = Auth::user()->employee_code;

        return $code ? Employee::where('emp_code', $code)->first() : null;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            // Stored as 09XXXXXXXXX — the format SMS notifications are sent to.
            'cell_number' => ['nullable', 'string', function ($attr, $value, $fail) {
                if (SmsService::normalize($value) === null) {
                    $fail('Enter a valid Philippine mobile number, e.g. 09171234567.');
                }
            }],
        ]);

        $user->fill([
            'name'  => $validated['name'],
            'email' => $validated['email'] ?: null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($employee = $this->employee()) {
            $this->cell_number = SmsService::normalize($this->cell_number) ?? '';
            $employee->update(['cell_number' => $this->cell_number ?: null]);
        }

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            {{ __("Update your name, email address and cellphone number.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model.live="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model.live="email" id="email" name="email" type="email" class="mt-1 block w-full" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-100">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 dark:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:ring-indigo-400">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($this->employee())
            <div>
                <x-input-label for="cell_number" :value="__('Cellphone Number')" />
                <x-text-input wire:model.live="cell_number" id="cell_number" name="cell_number" type="tel" inputmode="numeric" placeholder="09171234567" class="mt-1 block w-full" autocomplete="tel" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Leave, overtime and payroll SMS alerts are sent to this number.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('cell_number')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
