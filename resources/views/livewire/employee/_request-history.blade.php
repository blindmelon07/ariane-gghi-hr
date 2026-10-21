{{--
    "My requests" list for employee request forms.
    Expects: $title, $empty, $columns (labels), $rows ([req, values]), $confirm (cancel prompt).
    Optional: $statusLabels — status => label overrides.
              $viewRoute — route (taking the request) linked as $viewLabel ("View Pass") once approved,
              or for every status when $viewAlways is true.
--}}
@php
    $viewLabel ??= 'View Pass';
    $canView = fn ($req) => ! empty($viewRoute) && (! empty($viewAlways) || in_array($req->status, ['approved', 'completed'], true));
    $statusClass = fn ($status) => match ($status) {
        'approved', 'completed', 'released', 'received' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'rejected', 'declined' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
@endphp
<div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h4>
    </div>

    {{-- Cards (mobile) --}}
    <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($rows as $row)
            @php $req = $row['req']; @endphp
            <div class="px-4 py-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ implode(' · ', $row['values']) }}</p>
                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass($req->status) }}">{{ ($statusLabels ?? [])[$req->status] ?? ucfirst($req->status) }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $req->reason }}</p>
                @if ($req->remarks)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">Remarks: {{ $req->remarks }}</p>
                @endif
                <div class="flex gap-4">
                    @if ($canView($req))
                        <a href="{{ route($viewRoute, $req) }}" class="mt-2 inline-block text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $viewLabel }}</a>
                    @endif
                    @if ($req->status === 'pending')
                        <button wire:click="cancel({{ $req->id }})" wire:confirm="{{ $confirm }}"
                                class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">Cancel</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">{{ $empty }}</div>
        @endforelse
    </div>

    {{-- Table (desktop) --}}
    <div class="hidden lg:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @foreach ($columns as $column)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $column }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remarks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($rows as $row)
                    @php $req = $row['req']; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        @foreach ($row['values'] as $value)
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $value }}</td>
                        @endforeach
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $req->reason }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass($req->status) }}">{{ ($statusLabels ?? [])[$req->status] ?? ucfirst($req->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[180px] truncate">{{ $req->remarks ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canView($req))
                                <a href="{{ route($viewRoute, $req) }}" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">{{ $viewLabel }}</a>
                            @endif
                            @if ($req->status === 'pending')
                                <button wire:click="cancel({{ $req->id }})" wire:confirm="{{ $confirm }}"
                                        class="ml-3 text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-800">Cancel</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 4 }}" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">{{ $empty }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
