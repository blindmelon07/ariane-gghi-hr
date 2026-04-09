<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('HR Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <livewire:admin.dashboard />
    </div>
</x-app-layout>
