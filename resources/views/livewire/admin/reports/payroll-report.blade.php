<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">Payroll Report</span>
    </nav>

    {{-- Summary Cards --}}
    @if ($this->selectedPeriod)
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-green-100 rounded-lg">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Gross</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($this->totals['gross_pay'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-red-100 rounded-lg">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Deductions</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($this->totals['total_deductions'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-100 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Net Pay</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($this->totals['net_pay'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-indigo-100 rounded-lg">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Employees</p>
                <p class="text-xl font-bold text-indigo-600">{{ $this->payslips->count() }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 print:shadow-none">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Payroll Period</label>
                <select wire:model.live="periodId" class="rounded-lg border-gray-300 text-sm">
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
                <button wire:click="exportExcel" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </button>
                <button wire:click="exportPdf" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden print:shadow-none">
        <div wire:loading class="p-4 text-center text-gray-400 text-sm">Loading report data...</div>

        <div wire:loading.remove class="overflow-x-auto">
            @if ($this->payslips->isNotEmpty())
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
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
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->payslips as $slip)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-xs">{{ $slip->employee->employee_code ?? '' }}</td>
                        <td class="px-4 py-2.5 font-medium">{{ $slip->employee->full_name ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->days_present, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->basic_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($slip->overtime_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-medium">{{ number_format($slip->gross_pay, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($slip->sss_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($slip->philhealth_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($slip->pagibig_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($slip->tax_deduction, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($slip->other_deductions, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-700 font-medium">{{ number_format($slip->total_deductions, 2) }}</td>
                        <td class="px-4 py-2.5 text-right text-green-700 font-bold">{{ number_format($slip->net_pay, 2) }}</td>
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
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->totals['sss'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->totals['philhealth'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->totals['pagibig'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->totals['tax'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->totals['other'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-700">{{ number_format($this->totals['total_deductions'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-700">{{ number_format($this->totals['net_pay'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
                <div class="p-8 text-center text-gray-400">
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
