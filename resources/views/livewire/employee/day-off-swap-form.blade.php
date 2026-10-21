<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Swap Rest Day</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Work on one of your rest days and take a different day off instead. The change applies once your request is fully approved.</p>

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Scheduled Day Off <span class="text-red-500">*</span></label>
                    <select wire:model.live="originalDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                        <option value="">Select a rest day…</option>
                        @foreach ($this->restDays as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">You will report for work on this day.</p>
                    @error('originalDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date of Swap Off <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="newDate" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">You will be off on this day instead.</p>
                    @error('newDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea wire:model.live="reason" rows="3" placeholder="Explain why you need to swap (e.g. family event, covering for a co-worker)..."
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
            <h4 class="font-semibold text-gray-800 dark:text-gray-100">My Rest Day Swaps</h4>
        </div>
        {{-- Cards (mobile) --}}
        <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($this->myRequests as $req)
                @php
                    $sc = match($req->status) {
                        'approved'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                        default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    };
                @endphp
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $req->original_date->format('M d, Y') }} → {{ $req->new_date->format('M d, Y') }}
                        </p>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ ucfirst($req->status) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $req->reason }}</p>
                    @if ($req->remarks)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">Remarks: {{ $req->remarks }}</p>
                    @endif
                    @if ($req->status === 'pending')
                        <button wire:click="cancel({{ $req->id }})" wire:confirm="Cancel this swap request?"
                                class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">Cancel</button>
                    @endif
                </div>
            @empty
                <div class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">No rest day swap requests yet.</div>
            @endforelse
        </div>

        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Work On</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Day Off On</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remarks</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->myRequests as $req)
                        @php
                            $sc = match($req->status) {
                                'approved'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->original_date->format('D, M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->new_date->format('D, M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $req->reason }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sc }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[180px] truncate">{{ $req->remarks ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($req->status === 'pending')
                                    <button wire:click="cancel({{ $req->id }})" wire:confirm="Cancel this swap request?"
                                            class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-800">Cancel</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">No rest day swap requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
