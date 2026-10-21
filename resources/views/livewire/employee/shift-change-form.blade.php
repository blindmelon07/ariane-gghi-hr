<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Shift Change</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Work a different shift on specific dates. Your late and undertime are measured against the new shift once the request is fully approved; afterwards you return to your regular schedule.</p>

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="startDate" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('startDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">End Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="endDate" min="{{ $startDate ?: now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Same as the start date for a single day.</p>
                    @error('endDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">New Shift <span class="text-red-500">*</span></label>
                <select wire:model.live="scheduleId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">Select a shift…</option>
                    @foreach ($this->shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \App\Livewire\Admin\ShiftChangeApprovals::shiftTimes($shift) }})</option>
                    @endforeach
                </select>
                @error('scheduleId') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea wire:model.live="reason" rows="3" placeholder="Explain why you need a different shift (e.g. covering for a co-worker, school schedule)..."
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
    @include('livewire.employee._request-history', [
        'title'   => 'My Shift Changes',
        'empty'   => 'No shift change requests yet.',
        'columns' => ['Dates', 'Shift'],
        'rows'    => $this->myRequests->map(fn ($req) => [
            'req'    => $req,
            'values' => [
                \App\Livewire\Admin\ShiftChangeApprovals::dates($req),
                $req->schedule ? $req->schedule->name . ' (' . \App\Livewire\Admin\ShiftChangeApprovals::shiftTimes($req->schedule) . ')' : '—',
            ],
        ]),
        'confirm' => 'Cancel this shift change request?',
    ])
</div>
