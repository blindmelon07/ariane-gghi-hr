<div x-data="{ showModal: false }"
     @open-action-modal.window="showModal = true">

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">Leave Approvals</h3>

        {{-- Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search employee..."
                   class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">

            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
            </select>

            <select wire:model.live="filterType" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="">All Types</option>
                @foreach (\App\Models\LeaveType::all() as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterDept" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                <option value="">All Departments</option>
                @foreach (\App\Models\Employee::distinct()->pluck('department')->filter() as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3">Days</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Filed</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->pendingRequests as $req)
                        <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-100">{{ $req->employee->full_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $req->employee->emp_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->leaveType->code }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">
                                {{ $req->start_date->format('M d') }} – {{ $req->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->total_days }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-[180px] truncate">{{ $req->reason }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500">{{ $req->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($req->status) {
                                        'pending'   => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                        'approved'  => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                        'rejected'  => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                        default     => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $badgeClass }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($req->status === 'pending')
                                    <div class="flex gap-2">
                                        <button wire:click="openAction({{ $req->id }}, 'approve')"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium">Approve</button>
                                        <button wire:click="openAction({{ $req->id }}, 'reject')"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">Reject</button>
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">No leave requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pendingRequests->links() }}
        </div>
    </div>

    {{-- Action Modal --}}
    <template x-teleport="body">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
             @click.self="showModal = false" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    {{ $actionType === 'approve' ? 'Approve' : 'Reject' }} Leave Request
                </h4>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Remarks {{ $actionType === 'reject' ? '(required)' : '(optional)' }}
                    </label>
                    <textarea wire:model="remarks" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400 text-sm"
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
                                   {{ $actionType === 'approve' ? 'bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600' : 'bg-red-600 dark:bg-red-500 hover:bg-red-700 dark:hover:bg-red-600' }}">
                        {{ $actionType === 'approve' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
