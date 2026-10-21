<div x-data="{ showModal: false }"
     @open-approval-modal.window="showModal = true">

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
               class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
            <option value="all">All</option>
        </select>
    </div>

    @if ($this->myStep)
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-phosphor-user-bold class="w-4 h-4 text-indigo-500" />
            You are <span class="font-semibold text-indigo-600 dark:text-indigo-400 mx-1">{{ \App\Services\LeaveService::APPROVAL_STEPS[$this->myStep]['label'] ?? 'Approver' }}</span>
            — Step {{ $this->myStep }} of {{ count(\App\Services\LeaveService::APPROVAL_STEPS) }}
        </div>
    @endif

    @php
        $statusClass = fn ($status) => match ($status) {
            'approved'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        };
        $first = $this->requests->first();
        $detailLabels = $first ? array_keys($this->details($first)) : [];
    @endphp

    {{-- Cards (mobile) --}}
    <div class="lg:hidden space-y-3">
        @forelse ($this->requests as $req)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800 dark:text-gray-100 truncate">{{ $req->employee->full_name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $req->employee->emp_code }}</p>
                    </div>
                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass($req->status) }}">{{ ucfirst($req->status) }}</span>
                </div>
                <div class="grid grid-cols-1 gap-1 mt-2 text-xs text-gray-600 dark:text-gray-300">
                    @foreach ($this->details($req) as $label => $value)
                        <div>{{ $label }}: <span class="font-medium">{{ $value }}</span></div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 truncate">{{ $req->reason }}</p>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    @if ($req->status === 'pending' && ($req->approval_step === $this->myStep || $this->canOverride))
                        <div class="flex gap-4">
                            <button wire:click="openAction({{ $req->id }}, 'approve')" class="text-green-600 dark:text-green-400 text-xs font-medium">{{ $req->approval_step === $this->myStep ? 'Approve' : 'Approve (override)' }}</button>
                            <button wire:click="openAction({{ $req->id }}, 'reject')" class="text-red-600 dark:text-red-400 text-xs font-medium">Reject</button>
                        </div>
                    @elseif ($req->status === 'pending')
                        <span class="text-xs text-gray-400 dark:text-gray-500">Not your step</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-400 dark:text-gray-500 text-sm">No {{ strtolower($this->title) }} requests found.</div>
        @endforelse
    </div>

    {{-- Table (desktop / tablet) --}}
    <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                        @foreach ($detailLabels as $label)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $label }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->requests as $req)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $req->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $req->employee->emp_code }}</div>
                            </td>
                            @foreach ($this->details($req) as $value)
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200 font-medium">{{ $value }}</td>
                            @endforeach
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-[200px] truncate text-xs">{{ $req->reason }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass($req->status) }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($req->status === 'pending' && ($req->approval_step === $this->myStep || $this->canOverride))
                                    <div class="flex gap-2">
                                        <button wire:click="openAction({{ $req->id }}, 'approve')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium">{{ $req->approval_step === $this->myStep ? 'Approve' : 'Approve (override)' }}</button>
                                        <button wire:click="openAction({{ $req->id }}, 'reject')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">Reject</button>
                                    </div>
                                @elseif ($req->status === 'pending')
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Not your step</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($detailLabels) }}" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No {{ strtolower($this->title) }} requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $this->requests->links() }}</div>

    {{-- Action Modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display:none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    {{ $actionType === 'approve' ? 'Approve' : 'Reject' }} {{ $this->title }}
                </h4>
                @if ($this->canOverride && $actionType === 'approve')
                    <p class="mb-4 p-2 text-xs rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                        Super Admin override: this approves all remaining steps now, without waiting for the other approvers. It is recorded as an override in the activity log.
                    </p>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Remarks {{ $actionType === 'reject' ? '(required)' : '(optional)' }}
                    </label>
                    <textarea wire:model.live="remarks" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"
                              placeholder="Add remarks..."></textarea>
                    @error('remarks') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmAction" @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg transition
                                   {{ $actionType === 'approve' ? 'bg-green-600 dark:bg-green-500 hover:bg-green-700' : 'bg-red-600 dark:bg-red-500 hover:bg-red-700' }}">
                        {{ $actionType === 'approve' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
