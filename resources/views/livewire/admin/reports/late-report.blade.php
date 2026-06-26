<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600 dark:text-indigo-400">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Late Report</span>
    </nav>

    {{-- Summary Cards --}}
    @php $s = $this->summaryCards; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Late Incidents</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $s['total_incidents'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Late (min)</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($s['total_late_min']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Employees Affected</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $s['unique_employees'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-transparent dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Worst Late (min)</p>
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $s['worst_late_min'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select wire:model.live="department"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <option value="">All Departments</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Employee search --}}
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Employee</label>
                @if ($employeeId)
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700">
                            {{ \App\Models\Employee::find($employeeId)?->full_name }}
                        </span>
                        <button wire:click="clearEmployee" class="text-gray-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="empSearch"
                           placeholder="Search employee..."
                           @focus="open = true" @click.outside="open = false"
                           class="w-48 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                    @if ($empSearch)
                    <div x-show="open" class="absolute z-40 mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto" style="display:none">
                        @forelse ($this->employeeOptions as $opt)
                            <button wire:click="selectEmployee({{ $opt->id }})" @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                {{ $opt->full_name }}
                                <span class="text-gray-400 dark:text-gray-500">({{ $opt->emp_code }})</span>
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-gray-400">No match</div>
                        @endforelse
                    </div>
                    @endif
                @endif
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Emp Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time In</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Late (min)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Late (hrs)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->paginatedReport as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $row['emp_code'] }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['department'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['date'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['time_in'] }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-red-600 dark:text-red-400">
                                {{ $row['late_min'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-orange-600 dark:text-orange-400">
                                {{ number_format($row['late_min'] / 60, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    [$bg, $label] = match($row['status']) {
                                        'Late'      => ['bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', 'Late'],
                                        'Half-day'  => ['bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300', 'Half-day'],
                                        default     => ['bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300', $row['status']],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bg }}">{{ $label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                                No late records for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 print:hidden">{{ $this->paginatedReport->links() }}</div>
</div>
