<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">My Leave Requests</h3>

            <div class="flex items-center gap-3">
                <select wire:model.live="status" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:ring-indigo-400">
                    @for ($y = now()->year; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        @if ($this->requests->isEmpty())
            <div class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                <p class="text-gray-500 dark:text-gray-400 text-sm">No leave requests found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Start</th>
                            <th class="px-4 py-3">End</th>
                            <th class="px-4 py-3">Days</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($this->requests as $req)
                            <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $req->leaveType->code }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->start_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->end_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req->total_days }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-[200px] truncate">{{ $req->reason }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeClass = match($req->status) {
                                            'pending'   => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                            'approved'  => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                            'rejected'  => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                            'cancelled' => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                                            default     => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $badgeClass }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($req->status === 'pending')
                                        <button wire:click="cancel({{ $req->id }})"
                                                wire:confirm="Are you sure you want to cancel this request?"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium">
                                            Cancel
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
