<div>
    @php
        $cards = [
            [
                'label'   => 'Days Present',
                'value'   => $this->totalPresent,
                'icon'    => 'check-circle',
                'bg'      => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon_c'  => 'text-emerald-600 dark:text-emerald-400',
                'val_c'   => 'text-emerald-700 dark:text-emerald-300',
            ],
            [
                'label'   => 'Days Absent',
                'value'   => $this->totalAbsent,
                'icon'    => 'x-circle',
                'bg'      => 'bg-red-100 dark:bg-red-900/30',
                'icon_c'  => 'text-red-600 dark:text-red-400',
                'val_c'   => 'text-red-700 dark:text-red-300',
            ],
            [
                'label'   => 'Days Late',
                'value'   => $this->totalLate,
                'icon'    => 'clock',
                'bg'      => 'bg-amber-100 dark:bg-amber-900/30',
                'icon_c'  => 'text-amber-600 dark:text-amber-400',
                'val_c'   => 'text-amber-700 dark:text-amber-300',
            ],
            [
                'label'   => 'Total Hours',
                'value'   => $this->totalHours . ' h',
                'icon'    => 'presentation-chart',
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
                        <x-dynamic-component :component="'phosphor-' . $card['icon']" class="w-4 h-4 {{ $card['icon_c'] }}" />
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
