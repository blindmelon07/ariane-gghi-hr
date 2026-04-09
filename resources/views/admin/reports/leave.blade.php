<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leave Report') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <livewire:admin.reports.leave-report />
    </div>
</x-app-layout>
