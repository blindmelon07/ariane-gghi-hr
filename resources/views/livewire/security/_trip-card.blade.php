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
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            @else
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
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
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Scheduled: {{ $ticket->departure_datetime->format('M d, Y g:i A') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        Departed: {{ $ticket->departed_at->format('M d, Y g:i A') }}
                    </span>
                @endif

                @if ($ticket->return_datetime)
                    <span class="inline-flex items-center gap-1 text-xs {{ $overdue ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
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
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            Mark Departed
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Mark Returned
        @endif
    </button>
</div>
