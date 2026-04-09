<div>
    @php
        $cards = [
            [
                'label'   => 'Days Present',
                'value'   => $this->totalPresent,
                'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg'      => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon_c'  => 'text-emerald-600 dark:text-emerald-400',
                'val_c'   => 'text-emerald-700 dark:text-emerald-300',
            ],
            [
                'label'   => 'Days Absent',
                'value'   => $this->totalAbsent,
                'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg'      => 'bg-red-100 dark:bg-red-900/30',
                'icon_c'  => 'text-red-600 dark:text-red-400',
                'val_c'   => 'text-red-700 dark:text-red-300',
            ],
            [
                'label'   => 'Days Late',
                'value'   => $this->totalLate,
                'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg'      => 'bg-amber-100 dark:bg-amber-900/30',
                'icon_c'  => 'text-amber-600 dark:text-amber-400',
                'val_c'   => 'text-amber-700 dark:text-amber-300',
            ],
            [
                'label'   => 'Total Hours',
                'value'   => $this->totalHours . ' h',
                'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>',
                'bg'      => 'bg-indigo-100 dark:bg-indigo-900/30',
                'icon_c'  => 'text-indigo-600 dark:text-indigo-400',
                'val_c'   => 'text-indigo-700 dark:text-indigo-300',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($cards as $card)
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider leading-tight">
                        {{ $card['label'] }}
                    </p>
                    <div class="p-2 rounded-lg {{ $card['bg'] }}">
                        <svg class="w-4 h-4 {{ $card['icon_c'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $card['icon'] !!}
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold {{ $card['val_c'] }} tabular-nums" wire:loading.class="opacity-40">
                    {{ $card['value'] }}
                </p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">This month</p>
            </div>
        @endforeach
    </div>
</div>
