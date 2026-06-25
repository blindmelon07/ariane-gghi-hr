<div x-data="{ showModal: false }"
     @open-tc-modal.window="showModal = true">

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
               class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
        <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    @if ($this->myStep)
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            You are <span class="font-semibold text-indigo-600 dark:text-indigo-400 mx-1">{{ \App\Services\LeaveService::APPROVAL_STEPS[$this->myStep]['label'] ?? 'Approver' }}</span>
            — Step {{ $this->myStep }} of {{ count(\App\Services\LeaveService::APPROVAL_STEPS) }}
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM In</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM Out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM In</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM Out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->requests as $req)
                        @php
                            $sc = match($req->status) {
                                'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                default    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $req->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $req->employee->emp_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200 font-medium">{{ $req->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->am_time_in  ? \Carbon\Carbon::parse($req->am_time_in)->format('h:i A')  : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->am_time_out ? \Carbon\Carbon::parse($req->am_time_out)->format('h:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->pm_time_in  ? \Carbon\Carbon::parse($req->pm_time_in)->format('h:i A')  : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->pm_time_out ? \Carbon\Carbon::parse($req->pm_time_out)->format('h:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-[160px] truncate text-xs">{{ $req->reason }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($req->status === 'pending' && $req->approval_step === $this->myStep)
                                    <div class="flex gap-2">
                                        <button wire:click="openAction({{ $req->id }}, 'approve')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium">Approve</button>
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
                            <td colspan="9" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No time correction requests found.</td>
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
                    {{ $actionType === 'approve' ? 'Approve' : 'Reject' }} Time Correction
                </h4>
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
