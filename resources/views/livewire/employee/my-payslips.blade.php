<div>
    {{-- Year Selector --}}
    <div class="mb-6 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">Year:</label>
        <select wire:model.live="year" class="rounded-lg border-gray-300 text-sm">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
            @endfor
        </select>
    </div>

    {{-- Payslips --}}
    @if ($this->payslips->isEmpty())
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="mt-3 text-gray-500 text-sm">No payslips for {{ $year }}.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->payslips as $slip)
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $slip->payrollPeriod->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $slip->payrollPeriod->start_date->format('M d') }} – {{ $slip->payrollPeriod->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600">₱ {{ number_format($slip->net_pay, 2) }}</p>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Net Pay</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Basic Pay</p>
                            <p class="font-medium text-gray-900">₱ {{ number_format($slip->basic_pay, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Overtime</p>
                            <p class="font-medium text-gray-900">₱ {{ number_format($slip->overtime_pay, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Gross Pay</p>
                            <p class="font-medium text-gray-900">₱ {{ number_format($slip->gross_pay, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Deductions</p>
                            <p class="font-medium text-red-600">₱ {{ number_format($slip->total_deductions, 2) }}</p>
                        </div>
                    </div>

                    {{-- Deduction Breakdown --}}
                    <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
                        <span>SSS: ₱{{ number_format($slip->sss_deduction, 2) }}</span>
                        <span>PhilHealth: ₱{{ number_format($slip->philhealth_deduction, 2) }}</span>
                        <span>Pag-IBIG: ₱{{ number_format($slip->pagibig_deduction, 2) }}</span>
                        <span>Tax: ₱{{ number_format($slip->tax_deduction, 2) }}</span>
                        @if ($slip->other_deductions > 0)
                            <span>Others: ₱{{ number_format($slip->other_deductions, 2) }}</span>
                        @endif
                    </div>

                    {{-- Download --}}
                    <div class="mt-4 border-t pt-3 flex justify-end">
                        <a href="{{ route('payslips.download', $slip->id) }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download PDF
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
