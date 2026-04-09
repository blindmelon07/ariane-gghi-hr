<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Employee Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">

        {{-- Welcome Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Welcome back, {{ Auth::user()->name }}!
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Employee Code: {{ Auth::user()->employee_code }}</p>
        </div>

        {{-- Attendance Summary Stats --}}
        <div class="mb-6">
            <livewire:employee.attendance-summary />
        </div>

        {{-- Leave Balance --}}
        <div class="mb-6">
            <livewire:employee.leave-balance-card />
        </div>

        {{-- Attendance Calendar --}}
        <div>
            <livewire:employee.attendance-calendar />
        </div>
    </div>
</x-app-layout>
