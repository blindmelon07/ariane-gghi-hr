@php
    $isDeparting = $action === 'depart';
    $overdue     = $ticket->return_datetime?->isPast() && ! $isDeparting;
@endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border
            {{ $isDeparting ? 'border-blue-100 dark:border-blue-900/30 bg-blue-50/40 dark:bg-blue-950/10' : 'border-amber-100 dark:border-amber-900/30' }}
            hover:bg-gray-50 dark:hover:bg-gray-700/20 transition mb-3">

    <div class="flex items-start gap-4">
        {{-- Icon --}}
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5
                    {{ $isDeparting ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-amber-100 dark:bg-amber-900/30' }}">
            @if ($isDeparting)
                <x-phosphor-arrow-right-bold class="w-5 h-5 text-blue-500" />
            @else
                <x-phosphor-truck-bold class="w-5 h-5 text-amber-500" />
            @endif
        </div>

        <div>
            {{-- Vehicle --}}
            <p class="font-semibold text-gray-800 dark:text-gray-100">
                @if ($ticket->vehicle)
                    {{ $ticket->vehicle->display_name }}
                    <span class="ml-1 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $ticket->vehicle->plate_number }}</span>
                @else
                    <span class="text-gray-400 dark:text-gray-500">No vehicle assigned</span>
                @endif
            </p>

            {{-- Employee & route --}}
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                <span class="font-medium">{{ $ticket->employee->full_name }}</span>
                <span class="text-gray-400 mx-1">·</span>
                {{ $ticket->destination_from }} → {{ $ticket->destination_to }}
            </p>

            {{-- Driver --}}
            @if ($ticket->driver)
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    Driver: {{ $ticket->driver->display_name }}
                </p>
            @endif

            {{-- Timestamps --}}
            <div class="flex flex-wrap gap-3 mt-2">
                @if ($isDeparting)
                    <span class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 font-medium">
                        <x-phosphor-clock-bold class="w-3 h-3" />
                        Scheduled: {{ $ticket->departure_datetime->format('M d, Y g:i A') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <x-phosphor-arrow-right-bold class="w-3 h-3" />
                        Departed: {{ $ticket->departed_at->format('M d, Y g:i A') }}
                    </span>
                @endif

                @if ($ticket->return_datetime)
                    <span class="inline-flex items-center gap-1 text-xs {{ $overdue ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                        <x-phosphor-arrow-left-bold class="w-3 h-3" />
                        Expected return: {{ $ticket->return_datetime->format('M d, Y g:i A') }}
                        @if ($overdue)
                            <span class="px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-[10px]">OVERDUE</span>
                        @endif
                    </span>
                @endif
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-md truncate">{{ $ticket->purpose }}</p>
        </div>
    </div>

    {{-- Action button --}}
    <button wire:click="openAction({{ $ticket->id }}, '{{ $action }}')"
            class="shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition shadow-sm
                   {{ $isDeparting ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700' }}">
        @if ($isDeparting)
            <x-phosphor-arrow-right-bold class="w-4 h-4" />
            Mark Departed
        @else
            <x-phosphor-check-bold class="w-4 h-4" />
            Mark Returned
        @endif
    </button>
</div>
