<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Day Off Management') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <livewire:admin.day-off-manager />
    </div>
</x-app-layout>
