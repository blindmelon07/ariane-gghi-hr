<div x-data="{ selectedDay: null, selectedDate: null, selectedDayData: {} }">

    {{-- ── Card wrapper ── --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">

        {{-- ── Header: month navigation ── --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                </h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">Attendance Calendar</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="previousMonth"
                        class="p-2 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400
                               hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-gray-700 dark:hover:text-slate-200
                               transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button wire:click="nextMonth"
                        class="p-2 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400
                               hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-gray-700 dark:hover:text-slate-200
                               transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Legend ── --}}
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 px-6 py-3 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
            @foreach ([
                ['Present',  'bg-emerald-500'],
                ['Late',     'bg-amber-400'],
                ['Absent',   'bg-red-500'],
                ['Half-day', 'bg-orange-400'],
                ['Day-off',  'bg-slate-300 dark:bg-slate-600'],
            ] as [$label, $color])
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                    <span class="w-2.5 h-2.5 rounded-full {{ $color }} shrink-0"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>

        {{-- ── Day-of-week headers ── --}}
        <div class="grid grid-cols-7 border-b border-gray-100 dark:border-slate-800">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                <div class="py-2.5 text-center text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        {{-- ── Calendar grid ── --}}
        <div class="grid grid-cols-7">
            @php
                $firstDay    = \Carbon\Carbon::create($year, $month, 1);
                $startPadding = $firstDay->dayOfWeek;

                $statusConfig = [
                    'Present'  => ['dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800'],
                    'Late'     => ['dot' => 'bg-amber-400',   'badge' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 ring-1 ring-amber-200 dark:ring-amber-800'],
                    'Absent'   => ['dot' => 'bg-red-500',     'badge' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800'],
                    'Half-day' => ['dot' => 'bg-orange-400',  'badge' => 'bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 ring-1 ring-orange-200 dark:ring-orange-800'],
                    'Day-off'  => ['dot' => 'bg-slate-300 dark:bg-slate-600', 'badge' => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 ring-1 ring-slate-200 dark:ring-slate-700'],
                ];
            @endphp

            {{-- Empty cells before first day --}}
            @for ($i = 0; $i < $startPadding; $i++)
                <div class="min-h-[88px] border-b border-r border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50"></div>
            @endfor

            {{-- Day cells --}}
            @foreach ($this->attendanceData as $date => $day)
                @php
                    $cfg    = $statusConfig[$day['status']] ?? $statusConfig['Day-off'];
                    $isToday = $date === now()->toDateString();
                @endphp

                <div
                    class="min-h-[88px] border-b border-r border-gray-100 dark:border-slate-800 p-2 cursor-pointer
                           hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors group relative"
                    @click="
                        selectedDay  = '{{ $date }}';
                        selectedDate = '{{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}';
                        selectedDayData = {
                            status:           '{{ $day['status'] }}',
                            time_in:          '{{ $day['time_in'] ?? '' }}',
                            time_out:         '{{ $day['time_out'] ?? '' }}',
                            hours_worked:     '{{ $day['hours_worked'] ?? '0' }}',
                            minutes_late:     {{ (int)($day['minutes_late'] ?? 0) }},
                            minutes_undertime: {{ (int)($day['minutes_undertime'] ?? 0) }},
                            badge:            '{{ $cfg['badge'] }}',
                        };
                    "
                >
                    {{-- Day number --}}
                    <div class="flex items-center justify-between mb-1.5">
                        <span @class([
                            'text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full',
                            'bg-indigo-600 text-white'         => $isToday,
                            'text-gray-700 dark:text-slate-300' => !$isToday,
                        ])>{{ $day['day'] }}</span>

                        {{-- Status dot (only when there's data) --}}
                        @if ($day['status'] !== 'Day-off')
                            <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }} shrink-0"></span>
                        @endif
                    </div>

                    {{-- Badge --}}
                    <span class="inline-block px-1.5 py-0.5 text-[9px] font-semibold rounded-md {{ $cfg['badge'] }} leading-tight">
                        {{ $day['status'] }}
                    </span>

                    {{-- Time --}}
                    @if ($day['time_in'])
                        <div class="mt-1 text-[9px] text-gray-400 dark:text-slate-500 leading-tight font-mono">
                            {{ $day['time_in'] }}@if($day['time_out']) – {{ $day['time_out'] }}@endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Detail modal ── --}}
    <template x-teleport="body">
        <div
            x-show="selectedDay !== null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/70 px-4"
            @click.self="selectedDay = null"
            style="display:none;"
        >
            <div
                x-show="selectedDay !== null"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 w-full max-w-sm"
                @click.stop
            >
                {{-- Modal header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-slate-500 uppercase tracking-wider font-semibold mb-0.5">Attendance Details</p>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white" x-text="selectedDate"></h4>
                    </div>
                    <button @click="selectedDay = null"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal body --}}
                <div class="px-6 py-5 space-y-3">
                    {{-- Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-slate-400">Status</span>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg"
                              :class="selectedDayData.badge" x-text="selectedDayData.status"></span>
                    </div>

                    {{-- Time In --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-slate-400">Time In</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-slate-200 font-mono"
                              x-text="selectedDayData.time_in || '—'"></span>
                    </div>

                    {{-- Time Out --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-slate-400">Time Out</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-slate-200 font-mono"
                              x-text="selectedDayData.time_out || '—'"></span>
                    </div>

                    <div class="border-t border-gray-100 dark:border-slate-800 pt-3 space-y-3">
                        {{-- Hours Worked --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-slate-400">Hours Worked</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                  x-text="selectedDayData.hours_worked + ' hrs'"></span>
                        </div>

                        {{-- Minutes Late --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-slate-400">Minutes Late</span>
                            <span class="text-sm font-semibold"
                                  :class="selectedDayData.minutes_late > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-800 dark:text-slate-200'"
                                  x-text="selectedDayData.minutes_late + ' min'"></span>
                        </div>

                        {{-- Undertime --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-slate-400">Undertime</span>
                            <span class="text-sm font-semibold"
                                  :class="selectedDayData.minutes_undertime > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-800 dark:text-slate-200'"
                                  x-text="selectedDayData.minutes_undertime + ' min'"></span>
                        </div>
                    </div>
                </div>

                {{-- Modal footer --}}
                <div class="px-6 pb-5">
                    <button @click="selectedDay = null"
                            class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-medium text-gray-700 dark:text-slate-300
                                   hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
