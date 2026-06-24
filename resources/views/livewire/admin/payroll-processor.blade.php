<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/30 p-4 text-green-700 dark:text-green-300 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-950/30 p-4 text-red-700 dark:text-red-300 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Payroll Periods</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->employeeCount }} employees with salary configured</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                <option value="all">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="processing">Processing</option>
                <option value="processed">Processed</option>
                <option value="finalized">Finalized</option>
            </select>
            <button wire:click="$set('showCreate', true)" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Period
            </button>
        </div>
    </div>

    {{-- Create Period Modal --}}
    @if ($showCreate)
    <div wire:click.self="$set('showCreate', false)"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 dark:bg-black/60"
         x-data x-on:keydown.escape.window="$wire.set('showCreate', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Create Payroll Period</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">Dates are auto-filled from the cutoff type but you can change them to any custom range.</p>

            <form wire:submit="createPeriod" class="space-y-4">
                {{-- Period Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Period Name</label>
                    <input type="text" wire:model.live="periodName"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm"
                           placeholder="e.g. June 2026 - 1st Half" />
                    @error('periodName') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Cutoff Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Cutoff Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        {{-- 1st Cutoff --}}
                        <button type="button" wire:click="setCutoffType('semi_monthly_1')"
                                class="flex flex-col items-center gap-0.5 py-3 px-2 rounded-xl border-2 transition-colors
                                       {{ $cutoffType === 'semi_monthly_1'
                                           ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                           : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <span class="text-sm font-bold {{ $cutoffType === 'semi_monthly_1' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300' }}">
                                1st Cutoff
                            </span>
                            <span class="text-[11px] {{ $cutoffType === 'semi_monthly_1' ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">
                                SSS + PhilHealth
                            </span>
                        </button>

                        {{-- 2nd Cutoff --}}
                        <button type="button" wire:click="setCutoffType('semi_monthly_2')"
                                class="flex flex-col items-center gap-0.5 py-3 px-2 rounded-xl border-2 transition-colors
                                       {{ $cutoffType === 'semi_monthly_2'
                                           ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                           : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <span class="text-sm font-bold {{ $cutoffType === 'semi_monthly_2' ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300' }}">
                                2nd Cutoff
                            </span>
                            <span class="text-[11px] {{ $cutoffType === 'semi_monthly_2' ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">
                                Pag-IBIG
                            </span>
                        </button>

                        {{-- Monthly --}}
                        <button type="button" wire:click="setCutoffType('monthly')"
                                class="flex flex-col items-center gap-0.5 py-3 px-2 rounded-xl border-2 transition-colors
                                       {{ $cutoffType === 'monthly'
                                           ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                                           : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <span class="text-sm font-bold {{ $cutoffType === 'monthly' ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300' }}">
                                Monthly
                            </span>
                            <span class="text-[11px] {{ $cutoffType === 'monthly' ? 'text-green-500 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                All deductions
                            </span>
                        </button>

                        {{-- Custom --}}
                        <button type="button" wire:click="setCutoffType('custom')"
                                class="flex flex-col items-center gap-0.5 py-3 px-2 rounded-xl border-2 transition-colors
                                       {{ $cutoffType === 'custom'
                                           ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30'
                                           : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <span class="text-sm font-bold {{ $cutoffType === 'custom' ? 'text-amber-700 dark:text-amber-300' : 'text-gray-700 dark:text-gray-300' }}">
                                Custom
                            </span>
                            <span class="text-[11px] {{ $cutoffType === 'custom' ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">
                                Set own dates
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Date Range --}}
                <div class="rounded-xl border p-4
                            {{ $cutoffType === 'custom'
                                ? 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20' }}">
                    <p class="text-xs font-semibold mb-3 uppercase tracking-wide
                              {{ $cutoffType === 'custom' ? 'text-amber-600 dark:text-amber-400' : 'text-indigo-600 dark:text-indigo-400' }}">
                        {{ $cutoffType === 'custom' ? 'Custom Cut-off Dates — enter any range' : 'Cut-off Dates — auto filled, adjust if needed' }}
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" wire:model.live="startDate"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            @error('startDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" wire:model.live="endDate"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            @error('endDate') <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if ($cutoffType !== 'custom')
                        <p class="text-[11px] text-indigo-500 dark:text-indigo-400 mt-2">
                            Dates auto-filled from cutoff type. Change them if your actual cut-off dates differ.
                        </p>
                    @else
                        <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-2">
                            Enter your exact cut-off start and end dates. All deductions set to "Both Cutoffs" will apply.
                        </p>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" wire:click="$set('showCreate', false)"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:hover:bg-indigo-600">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Periods Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cutoff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payslips</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->periods as $period)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $period->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($period->cutoff_type)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $period->start_date->format('M d') }} – {{ $period->end_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['draft' => 'gray', 'processing' => 'yellow', 'processed' => 'blue', 'finalized' => 'green'];
                                $c = $colors[$period->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-300">
                                {{ ucfirst($period->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $period->payslips()->count() }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @if ($period->status === 'draft' || $period->status === 'processed')
                                <button wire:click="generatePayslips({{ $period->id }})" wire:confirm="Generate payslips for all employees?" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                                    {{ $period->status === 'draft' ? 'Generate' : 'Regenerate' }}
                                </button>
                            @endif
                            @if ($period->status === 'processed')
                                <button wire:click="finalize({{ $period->id }})" wire:confirm="Finalize this payroll? This cannot be undone." class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 font-medium">Finalize</button>
                            @endif
                            @if (in_array($period->status, ['processed', 'finalized']))
                                <button wire:click="exportExcel({{ $period->id }})" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 font-medium">Excel</button>
                            @endif
                            @if (in_array($period->status, ['processed', 'finalized']))
                                <button wire:click="viewPayslips({{ $period->id }})" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 font-medium">Payslips</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No payroll periods found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->periods->links() }}
    </div>

    {{-- Per-Employee Payslip Viewer Modal --}}
    @if ($viewPeriodId && $this->viewPeriod)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/70 p-4"
         x-data x-on:keydown.escape.window="$wire.closePayslips()">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Payslips — {{ $this->viewPeriod->name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->viewPeriod->start_date->format('M d') }} – {{ $this->viewPeriod->end_date->format('M d, Y') }}
                    </p>
                </div>
                <button wire:click="closePayslips" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Employee List --}}
            <div class="overflow-y-auto flex-1">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gross Pay</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deductions</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Net Pay</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($this->periodPayslips as $row)
                            @php $emp = $row['employee']; $slip = $row['payslip']; @endphp
                            <tr class="hover:bg-gray-50 dark:bg-gray-800/40">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $emp->full_name }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $emp->emp_code }}</p>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-mono text-gray-700 dark:text-gray-300">
                                    {{ $slip ? '₱ ' . number_format($slip->gross_pay, 2) : '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-mono text-red-600 dark:text-red-400">
                                    {{ $slip ? '₱ ' . number_format($slip->total_deductions, 2) : '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-mono font-semibold text-green-600 dark:text-green-400">
                                    {{ $slip ? '₱ ' . number_format($slip->net_pay, 2) : '—' }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ route('admin.payslips.download', [$emp->id, $viewPeriodId]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg
                                              {{ $slip ? 'bg-indigo-600 dark:bg-indigo-500 text-white hover:bg-indigo-700 dark:hover:bg-indigo-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}
                                              transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        {{ $slip ? 'Download' : 'Generate & Download' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
                "Generate & Download" computes and saves the payslip on the fly for employees without one.
            </div>
        </div>
    </div>
    @endif
</div>
