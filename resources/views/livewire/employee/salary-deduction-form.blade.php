@php
    use App\Models\SalaryDeductionLine;
    use App\Models\SalaryDeductionRequest;
    use App\Livewire\Employee\SalaryDeductionForm;
@endphp
<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-700 dark:text-green-300"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Salary Deduction Form</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-5">
            I hereby authorize the HR Staff to deduct the particular/s specified hereunder from my salary and no changes shall be made unless
            financial obligation/s due to me is already fully paid or such is supported by another Salary Deduction Form signed by me.
        </p>

        @if ($this->employee)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 text-sm">
                @foreach (['Name of the Employee' => $this->employee->full_name, 'Position' => $this->employee->position ?: '—', 'Department' => $this->employee->department ?: '—', 'Date of Request' => now()->format('M d, Y')] as $label => $value)
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <form wire:submit="submit" class="space-y-5">
            <div class="space-y-3">
                @foreach ($lines as $i => $line)
                    @php $loan = SalaryDeductionForm::isLoan($line['particular']); @endphp
                    <div wire:key="sd-line-{{ $i }}" class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Particulars</label>
                                <select wire:model.live="lines.{{ $i }}.particular" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                                    <option value="">Select…</option>
                                    @foreach (SalaryDeductionRequest::PARTICULARS as $key => [$label])
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error("lines.$i.particular") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            @if (SalaryDeductionForm::needsDescription($line['particular']))
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Please specify</label>
                                    <input type="text" wire:model.live="lines.{{ $i }}.description" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                    @error("lines.$i.description") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Amount per cut-off (₱)</label>
                                <input type="number" step="0.01" min="1" wire:model.live="lines.{{ $i }}.amount" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                @error("lines.$i.amount") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total to be paid (₱)</label>
                                @if ($loan)
                                    <input type="number" step="0.01" min="1" wire:model.live="lines.{{ $i }}.total" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                    @error("lines.$i.total") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                @else
                                    <p class="py-2 text-xs text-gray-400 dark:text-gray-500">Continuous — until stopped by a new form</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Cut-off</label>
                                <select wire:model.live="lines.{{ $i }}.cutoff" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                                    @foreach (SalaryDeductionLine::CUTOFF_LABELS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Start date</label>
                                <input type="date" wire:model.live="lines.{{ $i }}.starts_on" min="{{ now()->toDateString() }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                                @error("lines.$i.starts_on") <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if (count($lines) > 1)
                            <div class="text-right mt-2">
                                <button type="button" wire:click="removeLine({{ $i }})" class="text-xs font-medium text-red-600 dark:text-red-400">Remove</button>
                            </div>
                        @endif
                    </div>
                @endforeach

                <button type="button" wire:click="addLine"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                    <x-phosphor-plus-bold class="w-4 h-4" /> Add another particular
                </button>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                    Amortization table @if ($this->needsAttachment)<span class="text-red-500">*</span>@endif
                </label>
                <input type="file" wire:model="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">For loan deductions, please attach the amortization table issued by the bank/agency (PDF or photo, up to 5 MB).</p>
                <div wire:loading wire:target="attachment" class="text-xs text-gray-500 mt-1">Uploading…</div>
                @error('attachment') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @error('general') <p class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</p> @enderror

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="submit,attachment"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            </div>
        </form>
    </div>

    @include('livewire.employee._request-history', [
        'title'      => 'My Salary Deduction Requests',
        'empty'      => 'No salary deduction requests yet.',
        'columns'    => ['Date of Request', 'Per Cut-off'],
        'rows'       => $this->myRequests->map(fn ($r) => [
            'req'    => $r,
            'values' => [$r->created_at->format('M d, Y'), '₱' . number_format($r->totalPerCutoff(), 2)],
        ]),
        'confirm'    => 'Cancel this salary deduction request?',
        'viewRoute'  => 'salary-deduction.show',
        'viewLabel'  => 'View Form',
        'viewAlways' => true,
    ])
</div>
