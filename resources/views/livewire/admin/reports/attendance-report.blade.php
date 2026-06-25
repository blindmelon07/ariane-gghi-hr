<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600 dark:text-indigo-400">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Attendance Report</span>
    </nav>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
            </div>
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select wire:model.live="department" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative w-full sm:w-auto" x-data="{ open: false }">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Employee</label>
                @if ($employeeId)
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-800/50">
                            {{ \App\Models\Employee::find($employeeId)?->full_name }}
                        </span>
                        <button wire:click="clearEmployee" class="text-gray-400 dark:text-gray-500 hover:text-red-500 dark:text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="empSearch" placeholder="Search employee..."
                           @focus="open = true" @click.outside="open = false"
                           class="w-full sm:w-48 rounded-lg border-gray-300 dark:border-gray-600 text-sm" />
                    @if ($empSearch)
                    <div x-show="open" class="absolute z-40 mt-1 w-full sm:w-64 bg-white dark:bg-gray-800 border rounded-lg shadow-lg max-h-48 overflow-y-auto" style="display: none;">
                        @forelse ($this->employeeOptions as $opt)
                            <button wire:click="selectEmployee({{ $opt->id }})" @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 dark:bg-gray-800/50">
                                {{ $opt->full_name }} <span class="text-gray-400 dark:text-gray-500">({{ $opt->emp_code }})</span>
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">No match</div>
                        @endforelse
                    </div>
                    @endif
                @endif
            </div>
            <div class="flex gap-2 w-full sm:w-auto sm:ml-auto">
                <button wire:click="exportExcel" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </button>
                <button wire:click="exportPdf" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </button>
                <button onclick="window.print()" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:bg-gray-800/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden print:shadow-none">
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-xs font-medium print:hidden">Loading report...</div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Emp Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM In</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">AM Out</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM In</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PM Out</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hours</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Late (min)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->paginatedReport as $row)
                    <tr class="hover:bg-gray-50 dark:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-200">{{ $row['emp_code'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['department'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['am_time_in'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['am_time_out'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['pm_time_in'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['pm_time_out'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200 font-mono">{{ number_format($row['hours'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $row['late_min'] > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-300' }}">{{ $row['late_min'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $isLeave = !in_array($row['status'], ['Present','Late','Absent','Half-day','Incomplete','Day-off']);
                                $c = match(true) {
                                    $row['status'] === 'Present'    => 'green',
                                    $row['status'] === 'Late'       => 'yellow',
                                    $row['status'] === 'Absent'     => 'red',
                                    $row['status'] === 'Half-day'   => 'orange',
                                    $row['status'] === 'Incomplete' => 'purple',
                                    $row['status'] === 'Day-off'    => 'gray',
                                    $isLeave                        => 'blue',
                                    default                         => 'gray',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800 dark:bg-{{ $c }}-900/30 dark:text-{{ $c }}-300">{{ $row['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No attendance data for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="mt-4 print:hidden">{{ $this->paginatedReport->links() }}</div>
</div>
