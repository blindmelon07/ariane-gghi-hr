<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Request Time Correction</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Fill in only the time(s) that need to be corrected. Leave blank to keep the biometric record.</p>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date to Correct <span class="text-red-500">*</span></label>
                <input type="date" wire:model.live="date" max="{{ now()->toDateString() }}"
                       class="w-full sm:w-60 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Time Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">AM Time In</label>
                    <input type="time" wire:model.live="amTimeIn"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('amTimeIn') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">AM Time Out</label>
                    <input type="time" wire:model.live="amTimeOut"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">PM Time In</label>
                    <input type="time" wire:model.live="pmTimeIn"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">PM Time Out</label>
                    <input type="time" wire:model.live="pmTimeOut"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea wire:model.live="reason" rows="3" placeholder="Explain why the correction is needed (e.g. forgot to tap, device error)..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"></textarea>
                @error('reason') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- My Requests --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-800 dark:text-gray-100">My Time Correction Requests</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM In</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM Out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM In</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM Out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->myRequests as $req)
                        @php
                            $sc = match($req->status) {
                                'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                default    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->am_time_in ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->am_time_out ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->pm_time_in ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->pm_time_out ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[180px] truncate">{{ $req->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">No time correction requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
