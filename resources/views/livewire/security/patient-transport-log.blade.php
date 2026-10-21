<div class="mt-8">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                <x-phosphor-first-aid-kit-bold class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Patient Transport Log</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Record the ambulance's departure and arrival. Only HR-approved slips are listed.</p>
            </div>
        </div>
    </div>

    @php
        $sections = [
            ['title' => 'Ready to Depart', 'dot' => 'bg-blue-400', 'items' => $this->readyToDepart, 'empty' => 'No approved patient transports waiting.'],
            ['title' => 'Currently Out',   'dot' => 'bg-amber-400 animate-pulse', 'items' => $this->currentlyOut, 'empty' => 'No ambulance currently out.'],
        ];
    @endphp

    @foreach ($sections as $section)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full {{ $section['dot'] }}"></span>
                {{ $section['title'] }}
                <span class="ml-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold">{{ $section['items']->count() }}</span>
            </h4>

            @forelse ($section['items'] as $slip)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-3 last:mb-0 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0 text-sm">
                        <p class="font-semibold text-gray-800 dark:text-gray-100">
                            {{ $slip->plate_number }} <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $slip->driver_name }}</span>
                        </p>
                        <p class="text-gray-600 dark:text-gray-300 mt-0.5">Patient: {{ $slip->patient_name }} → {{ $slip->transported_to }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ $slip->series_no }} · {{ $slip->date->format('M d, Y') }}
                            @if ($slip->departed_at) · Departed {{ $slip->departed_at->timezone('Asia/Manila')->format('h:i A') }} @endif
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('patient-transport.show', $slip) }}" class="px-3 py-2 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200">View Slip</a>
                        @if (! $slip->departed_at)
                            <button wire:click="depart({{ $slip->id }})" wire:confirm="Record departure of {{ $slip->plate_number }} now?"
                                    class="px-3 py-2 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">Record Departure</button>
                        @else
                            <button wire:click="arrive({{ $slip->id }})" wire:confirm="Record arrival of {{ $slip->plate_number }} now?"
                                    class="px-3 py-2 text-xs font-medium rounded-lg bg-green-600 text-white hover:bg-green-700">Record Arrival</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">{{ $section['empty'] }}</p>
            @endforelse
        </div>
    @endforeach

    @if ($this->recentArrivals->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                Recently Arrived
            </h4>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                @foreach ($this->recentArrivals as $slip)
                    <div class="py-2 flex flex-wrap justify-between gap-2">
                        <span class="text-gray-800 dark:text-gray-100">{{ $slip->plate_number }} · {{ $slip->transported_to }}</span>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">
                            Out {{ $slip->departed_at?->timezone('Asia/Manila')->format('h:i A') }} · In {{ $slip->arrived_at?->timezone('Asia/Manila')->format('M d, h:i A') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
