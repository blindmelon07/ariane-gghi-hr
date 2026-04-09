<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Schedule Management') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <livewire:admin.schedule-manager />
    </div>
</x-app-layout>
