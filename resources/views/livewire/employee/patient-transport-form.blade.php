<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Gate Pass for Patient Transport</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">File before the ambulance leaves. HR approves it, and the guard on duty records the departure and arrival times.</p>

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="date" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('date') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Patient's Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="patientName"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @error('patientName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Transported To <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live="transportedTo" placeholder="e.g. Bicol Medical Center, Naga City"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                @error('transportedTo') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Ambulance Plate No. <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="plateNumber" list="ambulance-plates" autocomplete="off"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm uppercase" />
                    <datalist id="ambulance-plates">
                        @foreach ($this->ambulancePlates as $plate)
                            <option value="{{ $plate }}">
                        @endforeach
                    </datalist>
                    @error('plateNumber') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Driver <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="driverName" list="driver-names" autocomplete="off"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    <datalist id="driver-names">
                        @foreach ($this->driverNames as $name)
                            <option value="{{ $name }}">
                        @endforeach
                    </datalist>
                    @error('driverName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Names of Medical Staff <span class="text-red-500">*</span></label>
                <textarea wire:model.live="medicalStaff" rows="3" placeholder="One name per line"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"></textarea>
                @error('medicalStaff') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <span wire:loading.remove wire:target="submit">Submit Slip</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- My Slips --}}
    @include('livewire.employee._request-history', [
        'title'     => 'My Patient Transport Slips',
        'empty'     => 'No patient transport slips yet.',
        'columns'   => ['Series No.', 'Date', 'Patient', 'Transported To'],
        'rows'      => $this->mySlips->map(fn ($slip) => [
            'req'    => $slip,
            'values' => [$slip->series_no, $slip->date->format('D, M d, Y'), $slip->patient_name, $slip->transported_to],
        ]),
        'confirm'   => 'Cancel this patient transport slip?',
        'viewRoute' => 'patient-transport.show',
    ])
</div>
