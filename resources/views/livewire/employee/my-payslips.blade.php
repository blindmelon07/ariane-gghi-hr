<div>
    {{-- Year Selector --}}
    <div class="mb-6 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Year:</label>
        <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
            @endfor
        </select>
    </div>

    @if ($this->payslips->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-3 text-gray-500 dark:text-gray-400 text-sm">No payslips found for {{ $year }}.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->payslips as $slip)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    {{-- Period header --}}
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $slip->payrollPeriod->name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $slip->payrollPeriod->start_date->format('M d') }} – {{ $slip->payrollPeriod->end_date->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱ {{ number_format($slip->net_pay, 2) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">Net Pay</p>
                        </div>
                    </div>

                    {{-- Summary cards --}}
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Basic Pay</p>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">₱ {{ number_format($slip->basic_pay, 2) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Overtime Pay</p>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">₱ {{ number_format($slip->overtime_pay, 2) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Gross Pay</p>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">₱ {{ number_format($slip->gross_pay, 2) }}</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                            <p class="text-xs text-red-500 dark:text-red-400 mb-0.5">Total Deductions</p>
                            <p class="font-semibold text-red-600 dark:text-red-400">₱ {{ number_format($slip->total_deductions, 2) }}</p>
                        </div>
                    </div>

                    {{-- Deduction breakdown --}}
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">SSS ₱{{ number_format($slip->sss_deduction, 2) }}</span>
                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">PhilHealth ₱{{ number_format($slip->philhealth_deduction, 2) }}</span>
                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">Pag-IBIG ₱{{ number_format($slip->pagibig_deduction, 2) }}</span>
                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">Tax ₱{{ number_format($slip->tax_deduction, 2) }}</span>
                        @if ($slip->other_deductions > 0)
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">Others ₱{{ number_format($slip->other_deductions, 2) }}</span>
                        @endif
                    </div>

                    {{-- Download --}}
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <a href="{{ route('payslips.download', $slip->id) }}"
                           target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download PDF
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
