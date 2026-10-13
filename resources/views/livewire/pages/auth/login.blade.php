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
                    <svg class="w-4 h-4 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
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
                    <svg class="w-4 h-4 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
                {{-- Show / hide password --}}
                <button type="button" @click="show = !show"
                        :aria-label="show ? 'Hide password' : 'Show password'"
                        :title="show ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 focus:outline-none">
                    {{-- eye --}}
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{-- eye with slash --}}
                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
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
            <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
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
