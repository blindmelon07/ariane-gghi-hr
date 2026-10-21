<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    @php
        $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm';
        $label = 'block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1';
        $err   = 'text-red-500 dark:text-red-400 text-xs mt-1';
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">COE Request Form</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Request a Certificate of Employment. HR will prepare it and let you know when it is ready for pickup.</p>

        @if ($this->employee)
            <div class="grid grid-cols-2 gap-3 mb-5 text-sm">
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Complete Name of Employee</p>
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $this->employee->full_name }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Date</p>
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ now()->format('M d, Y') }}</p>
                </div>
            </div>
        @endif

        <form wire:submit="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Department <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="department" class="{{ $input }}" />
                    @error('department') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Designation <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="designation" class="{{ $input }}" />
                    @error('designation') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Date Hired</label>
                    <input type="date" wire:model="dateHired" max="{{ now()->toDateString() }}" class="{{ $input }}" />
                    @error('dateHired') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Date Separated <span class="text-xs font-normal text-gray-400">(if applicable)</span></label>
                    <input type="date" wire:model="dateSeparated" class="{{ $input }}" />
                    @error('dateSeparated') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Reason for COE Request <span class="text-red-500">*</span></label>
                <textarea wire:model="reason" rows="2" placeholder="e.g. Bank loan application, visa application, new employment..." class="{{ $input }}"></textarea>
                @error('reason') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}">Additional Requested Details to be Indicated in the COE</label>
                <textarea wire:model="additionalDetails" rows="2" placeholder="e.g. Include monthly salary, include employment status..." class="{{ $input }}"></textarea>
                @error('additionalDetails') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}">Signature of Employee <span class="text-red-500">*</span></label>
                <input type="text" wire:model="signatureName" placeholder="Type your full name" class="{{ $input }}" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Your typed name serves as your signature, dated today.</p>
                @error('signatureName') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            @error('general') <p class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</p> @enderror

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    @include('livewire.employee._request-history', [
        'title'      => 'My COE Requests',
        'empty'      => 'No COE requests yet.',
        'columns'    => ['Date', 'Release Date'],
        'rows'       => $this->myRequests->map(fn ($r) => [
            'req'    => $r,
            'values' => [$r->created_at->format('M d, Y'), $r->released_at?->format('M d, Y') ?? '—'],
        ]),
        'confirm'    => 'Cancel this COE request?',
        'viewRoute'  => 'coe.show',
        'statusLabels' => \App\Models\CoeRequest::STATUS_LABELS,
        'viewLabel'  => 'View Form',
        'viewAlways' => true,
    ])
</div>
