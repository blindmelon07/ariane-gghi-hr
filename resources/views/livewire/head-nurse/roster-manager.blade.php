<div x-data="{ showModal: false }" @open-roster-modal.window="showModal = true">

    {{-- Flash Message --}}
    @if (session('message'))
        <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-950/30 border border-green-300 dark:border-green-600 text-green-800 dark:text-green-300 px-4 py-3 text-sm"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nursing Duty Roster</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Click a cell to assign a shift or mark a nurse OFF for that date.
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">

        {{-- Month navigation --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ \Illuminate\Support\Carbon::create($year, $month, 1)->format('F Y') }}
            </h3>
            <div class="flex items-center gap-2">
                <button wire:click="previousMonth"
                        class="p-2 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400
                               hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button wire:click="nextMonth"
                        class="p-2 rounded-lg border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-slate-400
                               hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 px-6 py-3 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-400 shrink-0"></span> Day shift
            </span>
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span> Night shift
            </span>
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-600 shrink-0"></span> OFF
            </span>
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full border border-dashed border-gray-400 shrink-0"></span> Not scheduled
            </span>
        </div>

        {{-- Roster grid --}}
        <div class="overflow-x-auto">
            <table class="border-collapse text-xs w-full">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-slate-800/80 px-3 py-2 text-left font-semibold text-gray-600 dark:text-slate-300 border-b border-r border-gray-200 dark:border-slate-700 min-w-[160px]">
                            Nurse
                        </th>
                        @foreach ($this->dates as $d)
                            <th class="px-2 py-2 text-center font-medium border-b border-gray-200 dark:border-slate-700 min-w-[64px]
                                       {{ $d->isSunday() ? 'bg-gray-100 dark:bg-slate-800' : 'bg-gray-50 dark:bg-slate-800/50' }}">
                                <div class="text-gray-800 dark:text-slate-200">{{ $d->format('d') }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-slate-500 uppercase">{{ $d->format('D') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->nurses as $nurse)
                        <tr class="group">
                            <td class="sticky left-0 z-10 bg-white dark:bg-slate-900 group-hover:bg-gray-50 dark:group-hover:bg-slate-800/60 px-3 py-2 border-b border-r border-gray-200 dark:border-slate-700 whitespace-nowrap">
                                <div class="font-medium text-gray-800 dark:text-slate-200">{{ $nurse->first_name }} {{ $nurse->last_name }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-slate-500">{{ $nurse->emp_code }}</div>
                            </td>
                            @foreach ($this->dates as $d)
                                @php
                                    $key   = $nurse->id . '_' . $d->format('Y-m-d');
                                    $entry = $this->rosterMap[$key] ?? null;
                                    $sched = $entry['schedule'] ?? null;
                                @endphp
                                <td wire:click="openCell({{ $nurse->id }}, '{{ $d->format('Y-m-d') }}')"
                                    class="px-1.5 py-2 text-center border-b border-gray-100 dark:border-slate-800 cursor-pointer
                                           hover:ring-2 hover:ring-inset hover:ring-blue-400/50 transition-shadow">
                                    @if ($entry && $sched)
                                        <span title="{{ $sched['name'] }}"
                                              class="inline-flex items-center justify-center w-full rounded-md px-1 py-1 text-[10px] font-medium truncate
                                                     {{ $sched['is_night_shift'] ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300' }}">
                                            {{ \Illuminate\Support\Str::limit($sched['name'], 10, '') }}
                                        </span>
                                    @elseif ($entry)
                                        <span class="inline-flex items-center justify-center w-full rounded-md px-1 py-1 text-[10px] font-medium
                                                     bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                            OFF
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-slate-700">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->dates) + 1 }}" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-500">
                                No active nurses found in the Nursing department.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cell edit modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
                @php
                    $modalNurse = $selectedEmployeeId ? $this->nurses->firstWhere('id', $selectedEmployeeId) : null;
                @endphp
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">
                    Assign Duty
                </h4>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                    {{ $modalNurse ? $modalNurse->first_name . ' ' . $modalNurse->last_name : '' }}
                    @if ($selectedDate)
                        · {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('F d, Y (D)') }}
                    @endif
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Shift</label>
                    <select wire:model="selectedScheduleId"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">OFF (no shift)</option>
                        @foreach ($this->shiftTemplates as $sched)
                            <option value="{{ $sched->id }}">{{ $sched->name }} ({{ $sched->formatted_time }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-between gap-3">
                    <button wire:click="clearCell" @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 rounded-lg hover:bg-red-100 dark:hover:bg-red-950/50 transition">
                        Clear
                    </button>
                    <div class="flex gap-3">
                        <button @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button wire:click="saveCell" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 dark:bg-blue-500 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
