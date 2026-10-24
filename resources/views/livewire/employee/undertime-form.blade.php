<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Undertime</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Ask permission to leave before the end of your shift. An approved request excuses the undertime on your record; the minutes are still deducted from your pay.</p>

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date <span class="text-red-500">*</span></label>
                    <input id="date" type="date" wire:model.live="date" min="{{ now()->subDays(\App\Livewire\Employee\UndertimeForm::FILE_BACK_DAYS)->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="timeOut" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Time Out <span class="text-red-500">*</span></label>
                    <input id="timeOut" type="time" wire:model.live="timeOut"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">The time you will leave.</p>
                    @error('timeOut') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea id="reason" wire:model.live="reason" rows="3" placeholder="Explain why you need to leave early (e.g. medical appointment, family emergency)..."
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
        'title'   => 'My Undertime Requests',
        'empty'   => 'No undertime requests yet.',
        'columns' => ['Date', 'Time Out'],
        'rows'    => $this->myRequests->map(fn ($req) => [
            'req'    => $req,
            'values' => [$req->date->format('D, M d, Y'), $req->timeOutLabel()],
        ]),
        'confirm' => 'Cancel this undertime request?',
    ])
</div>
