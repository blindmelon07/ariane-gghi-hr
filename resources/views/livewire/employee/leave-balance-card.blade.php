<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Leave Balance</h3>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ now()->year }} allocation</p>
        </div>
        <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
            <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                      d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1014.25 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 109.75 7.5H12m0 0H7.5m4.5 0h4.5M12 7.5v13.5m4.5-13.5H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H12"/>
            </svg>
        </div>
    </div>

    @if (empty($this->balances))
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                </svg>
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
