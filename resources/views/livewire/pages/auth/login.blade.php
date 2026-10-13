<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirect(Auth::user()->dashboardRoute(), navigate: true);
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Sign in with your employee credentials</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">

        {{-- Employee Code --}}
        <div>
            <label for="employee_code" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                Employee Code
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <x-phosphor-user class="w-4 h-4 text-gray-400 dark:text-slate-500" />
                </div>
                <input
                    wire:model.live="form.employee_code"
                    id="employee_code"
                    type="text"
                    name="employee_code"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="e.g. EMP001"
                    class="block w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-slate-700
                           bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-slate-500
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-transparent
                           transition"
                />
            </div>
            <x-input-error :messages="$errors->get('form.employee_code')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                Password
            </label>
            <div class="relative" x-data="{ show: false }">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <x-phosphor-lock class="w-4 h-4 text-gray-400 dark:text-slate-500" />
                </div>
                {{-- Show / hide password --}}
                <button type="button" @click="show = !show"
                        :aria-label="show ? 'Hide password' : 'Show password'"
                        :title="show ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 focus:outline-none">
                    {{-- eye --}}
                    <x-phosphor-eye x-show="!show" class="w-4 h-4" />
                    {{-- eye with slash --}}
                    <x-phosphor-eye-slash x-show="show" x-cloak class="w-4 h-4" style="display: none;" />
                </button>
                <input
                    wire:model.live="form.password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    type="password"
                    style="padding-right: 2.75rem;"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="block w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-slate-700
                           bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-slate-500
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-transparent
                           transition"
                />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        {{-- Remember me --}}
        <div class="flex items-center">
            <input
                wire:model.live="form.remember"
                id="remember"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800"
            >
            <label for="remember" class="ml-2.5 text-sm text-gray-600 dark:text-slate-400">
                Keep me signed in
            </label>
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl
                   bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-700
                   text-white text-sm font-semibold
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900
                   transition-colors duration-150
                   disabled:opacity-60"
            wire:loading.attr="disabled"
        >
            <x-phosphor-circle-notch wire:loading class="w-4 h-4 animate-spin" />
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>

    </form>

    {{-- Divider --}}
    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800 text-center">
        <p class="text-xs text-gray-400 dark:text-slate-500">
            Having trouble? Contact your HR administrator.
        </p>
    </div>
</div>
