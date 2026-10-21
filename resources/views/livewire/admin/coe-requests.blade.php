<div x-data="{ showModal: false }" @open-coe-modal.window="showModal = true" @close-coe-modal.window="showModal = false">
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
               class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="open">To do (pending + ready for pickup)</option>
            <option value="pending">Pending</option>
            <option value="released">Ready for pickup</option>
            <option value="received">Received</option>
            <option value="rejected">Declined</option>
            <option value="cancelled">Cancelled</option>
            <option value="all">All</option>
        </select>
    </div>

    @php
        $badge = fn ($s) => match ($s) {
            'released', 'received' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'rejected'             => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'cancelled'            => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            default                => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        };
    @endphp

    <div class="space-y-3">
        @forelse ($this->requests as $req)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex-1 min-w-0 text-sm">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $req->employee->full_name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $req->employee->emp_code }}</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($req->status) }}">{{ \App\Models\CoeRequest::STATUS_LABELS[$req->status] ?? $req->status }}</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">{{ $req->designation }} · {{ $req->department }} · Requested {{ $req->created_at->format('M d, Y') }}</p>
                    <p class="text-gray-500 dark:text-gray-400 mt-0.5 truncate">Reason: {{ $req->reason }}</p>
                    @if ($req->additional_details)
                        <p class="text-gray-500 dark:text-gray-400 mt-0.5 truncate">Include: {{ $req->additional_details }}</p>
                    @endif
                    @if ($req->released_at)
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            Released {{ $req->released_at->format('M d, Y') }} by {{ $req->releaser?->name }}
                            @if ($req->received_at) · Received {{ $req->received_at->format('M d, Y') }} by {{ $req->received_by_name }} @endif
                        </p>
                    @endif
                </div>
                <div class="flex gap-3 shrink-0 text-xs font-medium">
                    <a href="{{ route('coe.show', $req) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">View</a>
                    @if ($req->status === 'pending')
                        <button wire:click="openAction({{ $req->id }}, 'release')" class="text-green-600 dark:text-green-400 hover:text-green-800">Release</button>
                        <button wire:click="openAction({{ $req->id }}, 'reject')" class="text-red-600 dark:text-red-400 hover:text-red-800">Decline</button>
                    @elseif ($req->status === 'released')
                        <button wire:click="openAction({{ $req->id }}, 'receive')" class="text-green-600 dark:text-green-400 hover:text-green-800">Mark Received</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-400 dark:text-gray-500 text-sm">No COE requests found.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $this->requests->links() }}</div>

    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display:none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4 space-y-4" @click.stop>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ ['release' => 'Release COE', 'receive' => 'Record COE Receipt', 'reject' => 'Decline COE Request'][$action] ?? '' }}
                </h4>

                @if ($action === 'receive')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">COE Received By</label>
                        <input type="text" wire:model="receivedBy" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('receivedBy') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($action !== 'reject')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ $action === 'receive' ? 'Receive Date' : 'Release Date' }}</label>
                        <input type="date" wire:model="date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                        @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($action !== 'receive')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Remarks {{ $action === 'reject' ? '(required)' : '(optional)' }}</label>
                        <textarea wire:model="remarks" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"></textarea>
                        @error('remarks') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg">Cancel</button>
                    <button wire:click="confirmAction"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg {{ $action === 'reject' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
