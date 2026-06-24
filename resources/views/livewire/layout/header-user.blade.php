<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false">

    {{-- Avatar trigger button --}}
    <button @click="open = !open"
            class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 transition-colors
                   hover:bg-gray-100 dark:hover:bg-slate-800 focus:outline-none"
            aria-label="User menu">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0 shadow">
            <span class="text-xs font-bold text-white">
                {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 2)) }}
            </span>
        </div>
        <div class="hidden sm:block text-left">
            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 leading-tight max-w-[120px] truncate">
                {{ Auth::user()?->name }}
            </p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 capitalize">
                {{ str_replace('_', ' ', Auth::user()?->role ?? '') }}
            </p>
        </div>
        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200 hidden sm:block"
             :class="open ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 top-full mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900
                border border-gray-100 dark:border-slate-800 shadow-xl z-50 overflow-hidden"
         style="display:none">

        {{-- User info header --}}
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0">
                    <span class="text-sm font-bold text-white">
                        {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 2)) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()?->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ Auth::user()?->employee_code }}</p>
                </div>
            </div>
        </div>

        {{-- Menu items --}}
        <div class="py-1">
            <a href="{{ route('profile') }}" wire:navigate @click="open = false"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300
                      hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                My Profile
            </a>
        </div>

        {{-- Divider + Logout --}}
        <div class="border-t border-gray-100 dark:border-slate-800 py-1">
            <button wire:click="logout"
                    wire:confirm="Are you sure you want to sign out?"
                    @click="open = false"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400
                           hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
                Sign Out
            </button>
        </div>
    </div>
</div>
