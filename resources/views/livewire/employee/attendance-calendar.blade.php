<div>
    {{-- Month Navigation --}}
    <div class="flex items-center justify-between mb-6">
        <button wire:click="previousMonth"
                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Prev
        </button>

        <h3 class="text-lg font-semibold text-gray-800">
            {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
        </h3>

        <button wire:click="nextMonth"
                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Next
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    {{-- Calendar Grid --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ selectedDay: null }">
        {{-- Day Headers --}}
        <div class="grid grid-cols-7 bg-gray-50 border-b">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                <div class="px-2 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $dayName }}</div>
            @endforeach
        </div>

        {{-- Calendar Days --}}
        <div class="grid grid-cols-7">
            @php
                $firstDay = \Carbon\Carbon::create($year, $month, 1);
                $startPadding = $firstDay->dayOfWeek; // 0=Sun
            @endphp

            {{-- Empty cells before first day --}}
            @for ($i = 0; $i < $startPadding; $i++)
                <div class="min-h-[90px] border-b border-r border-gray-100"></div>
            @endfor

            {{-- Day cells --}}
            @foreach ($this->attendanceData as $date => $day)
                @php
                    $badgeColors = match($day['status']) {
                        'Present' => 'bg-green-100 text-green-700',
                        'Late'    => 'bg-yellow-100 text-yellow-700',
                        'Absent'  => 'bg-red-100 text-red-700',
                        'Half-day' => 'bg-orange-100 text-orange-700',
                        'Day-off' => 'bg-gray-100 text-gray-500',
                        default   => 'bg-gray-100 text-gray-500',
                    };
                @endphp

                <div class="min-h-[90px] border-b border-r border-gray-100 p-2 cursor-pointer hover:bg-gray-50 transition"
                     @click="selectedDay = selectedDay === '{{ $date }}' ? null : '{{ $date }}'">
                    <div class="text-sm font-medium text-gray-800">{{ $day['day'] }}</div>

                    @if ($day['status'] !== 'Day-off' || $day['time_in'])
                        <div class="mt-1">
                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded {{ $badgeColors }}">
                                {{ $day['status'] }}
                            </span>
                        </div>

                        @if ($day['time_in'])
                            <div class="text-[10px] text-gray-500 mt-1 leading-tight">
                                {{ $day['time_in'] }}
                                @if ($day['time_out'])
                                    – {{ $day['time_out'] }}
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="mt-1">
                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded {{ $badgeColors }}">
                                {{ $day['status'] }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Alpine Modal for day detail --}}
                <template x-teleport="body">
                    <div x-show="selectedDay === '{{ $date }}'"
                         x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                         @click.self="selectedDay = null"
                         style="display: none;">
                        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm mx-4"
                             @click.stop>
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-semibold text-gray-800">
                                    {{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}
                                </h4>
                                <button @click="selectedDay = null" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Status</span>
                                    <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $badgeColors }}">{{ $day['status'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Time In</span>
                                    <span class="text-sm font-medium text-gray-800">{{ $day['time_in'] ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Time Out</span>
                                    <span class="text-sm font-medium text-gray-800">{{ $day['time_out'] ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Hours Worked</span>
                                    <span class="text-sm font-medium text-gray-800">{{ $day['hours_worked'] }} hrs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Minutes Late</span>
                                    <span class="text-sm font-medium {{ $day['minutes_late'] > 0 ? 'text-yellow-600' : 'text-gray-800' }}">{{ $day['minutes_late'] }} min</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Undertime</span>
                                    <span class="text-sm font-medium {{ $day['minutes_undertime'] > 0 ? 'text-orange-600' : 'text-gray-800' }}">{{ $day['minutes_undertime'] }} min</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            @endforeach
        </div>
    </div>
</div>
