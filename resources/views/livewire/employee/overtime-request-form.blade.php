<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6">File Overtime Request</h3>

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date of Overtime</label>
                    <input type="date" wire:model.live="date"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Requested OT Hours</label>
                    <input type="number" wire:model.live="requestedHours" step="0.5" min="0.5" max="8"
                           placeholder="e.g. 2.5"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('requestedHours') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason / Purpose</label>
                <textarea wire:model.live="reason" rows="3"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                          placeholder="Briefly describe why overtime is needed..."></textarea>
                @error('reason') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @error('general') <p class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</p> @enderror

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>

    {{-- My OT Requests --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h4 class="font-semibold text-gray-800 dark:text-gray-100">My Overtime Requests</h4>
        </div>

        @if ($this->myRequests->isEmpty())
            <div class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                No overtime requests yet.
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Requested Hrs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Approved Hrs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remarks</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($this->myRequests as $req)
                        @php
                            $badge = match($req->status) {
                                'pending'   => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                'approved'  => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                'rejected'  => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                'cancelled' => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                                default     => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-6 py-3 text-sm text-gray-800 dark:text-gray-100 font-medium">{{ $req->date->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono">{{ $req->requested_hours }}h</td>
                            <td class="px-6 py-3 text-sm font-mono {{ $req->approved_hours ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $req->approved_hours ? $req->approved_hours . 'h' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-[200px] truncate">{{ $req->reason }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $badge }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400 dark:text-gray-500 max-w-[160px] truncate">{{ $req->remarks ?? '—' }}</td>
                            <td class="px-6 py-3">
                                @if ($req->status === 'pending')
                                    <button wire:click="cancel({{ $req->id }})" wire:confirm="Cancel this OT request?"
                                            class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 font-medium">
                                        Cancel
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
