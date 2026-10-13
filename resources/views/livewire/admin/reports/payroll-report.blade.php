<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600 dark:text-indigo-400">Dashboard</a>
        <x-phosphor-caret-right-bold class="w-4 h-4" />
        <span class="text-gray-700 dark:text-gray-200 font-medium">Payroll Report</span>
    </nav>

    {{-- Summary Cards --}}
    @if ($this->selectedPeriod)
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <x-phosphor-currency-circle-dollar class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total Gross</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->totals['gross_pay'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <x-phosphor-minus-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total Deductions</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->totals['total_deductions'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <x-phosphor-money class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total Net Pay</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->totals['net_pay'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                <x-phosphor-users class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Employees</p>
                <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ $this->payslips->count() }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 print:shadow-none">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payroll Period</label>
                <select wire:model.live="periodId" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">Select Period</option>
                    @foreach ($this->periods as $period)
                        <option value="{{ $period->id }}">
                            {{ $period->start_date->format('M d') }} &ndash; {{ $period->end_date->format('M d, Y') }}
                            ({{ ucfirst($period->status) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 ml-auto print:hidden">
                <button wire:click="exportExcel" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 dark:bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition">
                    <x-phosphor-file-arrow-down-bold class="w-4 h-4" />
                    Excel
                </button>
                <button wire:click="exportPdf" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 dark:bg-red-500 text-white text-sm font-medium rounded-lg hover:bg-red-700 dark:hover:bg-red-600 transition">
                    <x-phosphor-file-arrow-down-bold class="w-4 h-4" />
                    PDF
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                    <x-phosphor-printer-bold class="w-4 h-4" />
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden print:shadow-none">
        <div wire:loading class="p-4 text-center text-gray-400 dark:text-gray-500 text-sm">Loading report data...</div>

        {{-- Cards (mobile) --}}
        <div wire:loading.remove class="lg:hidden divide-y divide-gray-100 dark:divide-gray-700 print:hidden">
            @forelse ($this->payslips as $slip)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $slip->employee->full_name ?? '' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $slip->employee->emp_code ?? '' }} · {{ number_format($slip->days_present, 1) }} days</p>
                        </div>
                        <p class="shrink-0 text-right font-bold text-green-700 dark:text-green-300">{{ number_format($slip->net_pay, 2) }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                        <div><p class="text-gray-400 dark:text-gray-500">Basic</p>{{ number_format($slip->basic_pay, 2) }}</div>
                        <div><p class="text-gray-400 dark:text-gray-500">OT</p>{{ number_format($slip->overtime_pay, 2) }}</div>
                        <div><p class="text-gray-400 dark:text-gray-500">Gross</p><span class="font-medium">{{ number_format($slip->gross_pay, 2) }}</span></div>
                    </div>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                        Deductions: {{ number_format($slip->total_deductions, 2) }}
                        <span class="text-gray-400 dark:text-gray-500">(SSS {{ number_format($slip->sss_deduction, 2) }} · PH {{ number_format($slip->philhealth_deduction, 2) }} · PI {{ number_format($slip->pagibig_deduction, 2) }} · Tax {{ number_format($slip->tax_deduction, 2) }} · Other {{ number_format($slip->other_deductions, 2) }})</span>
                    </p>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                    @if ($periodId)
                        No payslips found for this period.
                    @else
                        Select a payroll period to view the report.
                    @endif
                </div>
            @endforelse
            @if ($this->payslips->isNotEmpty())
                <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 font-bold text-sm flex items-center justify-between">
                    <span>TOTALS</span>
                    <span class="text-green-700 dark:text-green-300">{{ number_format($this->totals['net_pay'], 2) }}</span>
                </div>
            @endif
        </div>

        <div wire:loading.remove class="hidden lg:block overflow-x-auto print:block">
            @if ($this->payslips->isNotEmpty())
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Emp Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3 text-right">Days</th>
                        <th class="px-4 py-3 text-right">Basic</th>
                        <th class="px-4 py-3 text-right">OT</th>
                        <th class="px-4 py-3 text-right">Gross</th>
                        <th class="px-4 py-3 text-right">SSS</th>
                        <th class="px-4 py-3 text-right">PH</th>
                        <th class="px-4 py-3 text-right">PI</th>
                        <th class="px-4 py-3 text-right">Tax</th>
                        <th class="px-4 py-3 text-right">Others</th>
                        <th class="px-4 py-3 text-right">Deductions</th>
                        <th class="px-4 py-3 text-right">Net Pay</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($this->payslips as $slip)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $slip->employee->emp_code ?? '' }}</td>
                        <td class="px-4 py-2.5 font-medium">{{ $slip->employee->full_name ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->days_present, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->basic_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->overtime_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-medium">{{ number_format($slip->gross_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ number_format($slip->sss_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ number_format($slip->philhealth_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ number_format($slip->pagibig_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ number_format($slip->tax_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ number_format($slip->other_deductions, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-700 dark:text-red-300 font-medium">{{ number_format($slip->total_deductions, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-green-700 dark:text-green-300 font-bold">{{ number_format($slip->net_pay, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-bold text-sm">
                    <tr>
                        <td class="px-4 py-3" colspan="2">TOTALS</td>
                        <td class="px-4 py-3 text-right"></td>
                        <td class="px-4 py-3 text-right">{{ number_format($this->totals['basic_pay'], 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($this->totals['overtime_pay'], 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($this->totals['gross_pay'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($this->totals['sss'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($this->totals['philhealth'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($this->totals['pagibig'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($this->totals['tax'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($this->totals['other'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-700 dark:text-red-300">{{ number_format($this->totals['total_deductions'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-700 dark:text-green-300">{{ number_format($this->totals['net_pay'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                    @if ($periodId)
                        No payslips found for this period.
                    @else
                        Select a payroll period to view the report.
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
