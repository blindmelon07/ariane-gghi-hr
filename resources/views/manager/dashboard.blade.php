<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Manager Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">

        {{-- Welcome Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-transparent dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Welcome, {{ Auth::user()->name }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manager · {{ Auth::user()->employee_code }}</p>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-phosphor-users-three class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Team Members</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">14</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <x-phosphor-warning class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Approvals</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">3</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <x-phosphor-calendar-blank class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Present Today</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">11</p>
                </div>
            </div>

        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Quick Actions</h4>
            <div class="flex flex-wrap gap-3">
                <button class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors">
                    Review Approvals
                </button>
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    View Team Attendance
                </button>
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    My Team
                </button>
            </div>
        </div>

    </div>
</x-app-layout>
