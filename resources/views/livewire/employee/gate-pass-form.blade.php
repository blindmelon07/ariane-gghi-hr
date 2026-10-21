<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Gate Pass</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Ask permission to leave the hospital premises during work hours. Once HR approves, open the pass below and show it to the guard on your way out.</p>

        @if ($this->employee)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5 text-sm">
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Name</p>
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $this->employee->full_name }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Position</p>
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $this->employee->position ?: '—' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Department</p>
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $this->employee->department ?: '—' }}</p>
                </div>
            </div>
        @endif

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="date" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Time Out <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live="timeOut"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('timeOut') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Time of Arrival <span class="text-red-500">*</span></label>
                    <input type="time" wire:model.live="timeOfArrival"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">When you expect to be back.</p>
                    @error('timeOfArrival') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Destination <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live="destination" placeholder="e.g. BDO Sorsogon City branch"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                @error('destination') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason/s <span class="text-red-500">*</span></label>
                <textarea wire:model.live="reason" rows="3" placeholder="Why you need to go out (e.g. bank errand for the office, personal emergency)..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"></textarea>
                @error('reason') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <span wire:loading.remove wire:target="submit">Submit Gate Pass</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- My Gate Passes --}}
    @include('livewire.employee._request-history', [
        'title'     => 'My Gate Passes',
        'empty'     => 'No gate passes yet.',
        'columns'   => ['Series No.', 'Date', 'Out / Return', 'Destination'],
        'rows'      => $this->myPasses->map(fn ($pass) => [
            'req'    => $pass,
            'values' => [
                $pass->series_no,
                $pass->date->format('D, M d, Y'),
                $pass->timeOutLabel() . ' – ' . $pass->timeOfArrivalLabel(),
                $pass->destination,
            ],
        ]),
        'confirm'   => 'Cancel this gate pass?',
        'viewRoute' => 'gate-pass.show',
    ])
</div>
