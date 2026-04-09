<div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-4">Leave Balance ({{ now()->year }})</h3>

        @if (empty($this->balances))
            <p class="text-sm text-gray-400">No credit data available.</p>
        @else
            <div class="space-y-4">
                @foreach ($this->balances as $balance)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $balance['name'] }} ({{ $balance['code'] }})</span>
                            <span class="font-semibold text-gray-800">{{ $balance['remaining'] }} / {{ $balance['total'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php
                                $barColor = $balance['percent'] > 50 ? 'bg-green-500' : ($balance['percent'] > 20 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp
                            <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-300" style="width: {{ $balance['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
