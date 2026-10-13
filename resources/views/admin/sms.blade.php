<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('SMS Center') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <livewire:admin.sms-center />
    </div>
</x-app-layout>
