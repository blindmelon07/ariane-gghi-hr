<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    {{-- Request Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Overtime Request Form</h3>
        <p class="text-xs font-medium text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/30 rounded-lg px-3 py-2 mb-5">
            Note: All overtime requests shall be approved before the overtime is rendered — by your Department Head, HR, and the CEO / Medical Director.
        </p>

        @if ($this->employee)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5 text-sm">
                @foreach ([
                    'Full Name'                => $this->employee->full_name,
                    'Biometrics ID'            => $this->employee->biotime_id ?: $this->employee->emp_code,
                    'Department'               => $this->employee->department ?: '—',
                    'Position'                 => $this->employee->position ?: '—',
                    'Regular Working Schedule' => \Carbon\Carbon::parse($this->regularSchedule['from'])->format('g:i A') . ' to ' . \Carbon\Carbon::parse($this->regularSchedule['to'])->format('g:i A'),
                    'Date Requested'           => now()->format('M d, Y'),
                ] as $label => $value)
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <form wire:submit="submit" class="space-y-5">
            {{-- Proposed overtime hours per date --}}
            <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Proposed Overtime Hours per Date</p>
                <div class="space-y-3">
                    @foreach ($lines as $i => $line)
                        <div wire:key="ot-line-{{ $i }}" class="grid grid-cols-2 sm:grid-cols-[1.4fr_1fr_1fr_auto_auto] gap-2 items-start rounded-lg border border-gray-200 dark:border-gray-700 p-3 sm:p-2">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Date</label>
                                <input type="date" wire:model.live="lines.{{ $i }}.date" min="{{ now()->toDateString() }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                @error("lines.$i.date") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">From</label>
                                <input type="time" wire:model.live="lines.{{ $i }}.from"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                @error("lines.$i.from") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">To</label>
                                <input type="time" wire:model.live="lines.{{ $i }}.to"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                @error("lines.$i.to") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:text-right">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Hours</label>
                                <p class="py-2 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ number_format($this->lineHours[$i] ?? 0, 2) }}</p>
                            </div>
                            <div class="flex items-end justify-end sm:pt-5">
                                @if (count($lines) > 1)
                                    <button type="button" wire:click="removeLine({{ $i }})" title="Remove this date"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                        <x-phosphor-trash-bold class="w-4 h-4" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between mt-3">
                    @if (count($lines) < \App\Livewire\Employee\OvertimeRequestForm::MAX_LINES)
                        <button type="button" wire:click="addLine"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                            <x-phosphor-plus-bold class="w-4 h-4" /> Add date
                        </button>
                    @else
                        <span></span>
                    @endif
                    <p class="text-sm text-gray-700 dark:text-gray-200">Total Overtime Hours: <span class="font-bold">{{ number_format($this->totalHours, 2) }}</span></p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Purpose and Accomplishment <span class="text-red-500">*</span></label>
                <textarea wire:model.live="purpose" rows="4"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                          placeholder="Why the overtime is needed and what will be accomplished..."></textarea>
                @error('purpose') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
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

    {{-- My Overtime Requests --}}
    @include('livewire.employee._request-history', [
        'title'     => 'My Overtime Requests',
        'empty'     => 'No overtime requests yet.',
        'columns'   => ['Date Requested', 'Date/s', 'Total Hours'],
        'rows'      => $this->myForms->map(fn ($form) => [
            'req'    => $form,
            'values' => [
                $form->created_at->format('M d, Y'),
                $form->lines->map(fn ($l) => $l->date->format('M d'))->join(', ') ?: '—',
                number_format($form->totalHours(), 2),
            ],
        ]),
        'confirm'   => 'Cancel this overtime request?',
        'viewRoute'  => 'overtime.show',
        'viewLabel'  => 'View Form',
        'viewAlways' => true,
    ])
</div>
