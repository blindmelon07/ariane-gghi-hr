<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600 dark:text-indigo-400">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Overtime Report</span>
    </nav>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        @php $s = $this->summaryCards; @endphp

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Requests</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $s['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $s['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Approved</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $s['approved'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Requested Hrs</p>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($s['requested_hours'], 1) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Approved Hrs</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($s['approved_hours'], 1) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Year</label>
                <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Month</label>
                <select wire:model.live="month" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <option value="">All Months</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select wire:model.live="department" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <option value="">All Departments</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select wire:model.live="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="sm:ml-auto">
                <button onclick="window.print()"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden print:shadow-none">
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-xs font-medium print:hidden">
            Loading...
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Req. Hours</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Appr. Hours</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Approved By</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->reportData as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                {{ $row->employee->full_name ?? '—' }}
                                <span class="block text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $row->employee->emp_code ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->employee->department ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-700 dark:text-gray-200">{{ number_format($row->requested_hours, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-700 dark:text-gray-200">
                                {{ $row->approved_hours !== null ? number_format($row->approved_hours, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    [$bg, $label] = match($row->status) {
                                        'approved' => ['bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300', 'Approved'],
                                        'rejected' => ['bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'Rejected'],
                                        default    => ['bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300', 'Pending'],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bg }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->approver?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $row->reason }}">{{ $row->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                                No overtime records for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 print:hidden">{{ $this->reportData->links() }}</div>
</div>
