<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Leave Balance</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ now()->year }} allocation</p>
        </div>
        <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
            <x-phosphor-gift class="w-4 h-4 text-violet-600 dark:text-violet-400" />
        </div>
    </div>

    @if (empty($this->balances))
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                <x-phosphor-question class="w-5 h-5 text-gray-400 dark:text-slate-500" />
            </div>
            <p class="text-sm text-gray-400 dark:text-slate-500">No credit data available.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach ($this->balances as $balance)
                @php
                    $pct = $balance['percent'];
                    if ($pct > 60)      { $barColor = 'bg-emerald-500'; $textColor = 'text-emerald-600 dark:text-emerald-400'; }
                    elseif ($pct > 25)  { $barColor = 'bg-amber-400';   $textColor = 'text-amber-600 dark:text-amber-400'; }
                    else                { $barColor = 'bg-red-500';      $textColor = 'text-red-600 dark:text-red-400'; }
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $balance['name'] }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ $balance['code'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold {{ $textColor }}">{{ $balance['remaining'] }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500">of {{ $balance['total'] }} days</p>
                        </div>
                    </div>

                    {{-- Track --}}
                    <div class="w-full h-2 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
