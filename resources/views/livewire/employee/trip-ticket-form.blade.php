<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Request Form for Vehicle and Driver</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">GSAC General Hospital Inc. — Trip Ticket</p>
            </div>
        </div>

        @error('general')
            <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-950/30 p-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Destination From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Destination — From <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model.live="destination_from"
                       placeholder="e.g. GGHI Main Campus"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                @error('destination_from') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Destination To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Destination — To <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model.live="destination_to"
                       placeholder="e.g. DOH Regional Office"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                @error('destination_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date and Time of Departure --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Date and Time of Departure <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" wire:model.live="departure_datetime"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                @error('departure_datetime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date and Time of Return --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Date and Time of Return
                </label>
                <input type="datetime-local" wire:model.live="return_datetime"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                @error('return_datetime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Vehicle to be used --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Vehicle to be Used
                </label>
                <select wire:model.live="vehicle_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">— No preference / To be assigned —</option>
                    @foreach ($this->availableVehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->display_name }} · {{ $v->vehicle_type }} · {{ $v->capacity }} seats</option>
                    @endforeach
                </select>
                @error('vehicle_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Driver --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Requested Driver
                </label>
                <select wire:model.live="driver_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">— No preference / To be assigned —</option>
                    @foreach ($this->availableDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->employee->full_name ?? '—' }}</option>
                    @endforeach
                </select>
                @error('driver_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Passengers (full width) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Passengers
                </label>
                <textarea wire:model.live="passengers" rows="3"
                          placeholder="List passenger names, one per line or comma-separated..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"></textarea>
                @error('passengers') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Purpose (full width) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Purpose <span class="text-red-500">*</span>
                </label>
                <textarea wire:model.live="purpose" rows="3"
                          placeholder="Briefly describe the purpose of this trip..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"></textarea>
                @error('purpose') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Approval chain note --}}
        <div class="mt-6 rounded-lg bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 p-4">
            <p class="text-xs text-blue-700 dark:text-blue-400 font-medium mb-1">Approval Chain</p>
            <div class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                @foreach (\App\Services\TripTicketService::APPROVAL_STEPS as $step => $config)
                    @if ($step > 1)
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    @endif
                    <span class="flex items-center gap-1">
                        <span class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center font-bold text-[10px]">{{ $step }}</span>
                        {{ $config['label'] }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button wire:click="submit" wire:loading.attr="disabled"
                    class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-60 flex items-center gap-2">
                <span wire:loading.remove wire:target="submit">Submit Trip Ticket</span>
                <span wire:loading wire:target="submit">Submitting...</span>
            </button>
        </div>
    </div>
</div>
