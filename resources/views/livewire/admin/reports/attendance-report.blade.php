<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">Attendance Report</span>
    </nav>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="rounded-lg border-gray-300 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="rounded-lg border-gray-300 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                <select wire:model.live="department" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative" x-data="{ open: false }">
                <label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                @if ($employeeId)
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm bg-gray-50">
                            {{ \App\Models\Employee::find($employeeId)?->full_name }}
                        </span>
                        <button wire:click="clearEmployee" class="text-gray-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="empSearch" placeholder="Search employee..."
                           @focus="open = true" @click.outside="open = false"
                           class="rounded-lg border-gray-300 text-sm w-48" />
                    @if ($empSearch)
                    <div x-show="open" class="absolute z-40 mt-1 w-64 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto" style="display: none;">
                        @forelse ($this->employeeOptions as $opt)
                            <button wire:click="selectEmployee({{ $opt->id }})" @click="open = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50">
                                {{ $opt->full_name }} <span class="text-gray-400">({{ $opt->emp_code }})</span>
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-gray-400">No match</div>
                        @endforelse
                    </div>
                    @endif
                @endif
            </div>
            <div class="flex gap-2 ml-auto">
                <button wire:click="exportExcel" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </button>
                <button wire:click="exportPdf" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden print:shadow-none">
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 text-indigo-600 text-xs font-medium print:hidden">Loading report...</div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Emp Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time In</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Out</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hours</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Late (min)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($this->paginatedReport as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-700">{{ $row['emp_code'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row['department'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row['time_in'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row['time_out'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700 font-mono">{{ number_format($row['hours'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $row['late_min'] > 0 ? 'text-red-600 font-medium' : 'text-gray-600' }}">{{ $row['late_min'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $c = match($row['status']) {
                                    'Present' => 'green', 'Late' => 'yellow', 'Absent' => 'red', 'Half-day' => 'orange', default => 'gray'
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">{{ $row['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">No attendance data for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 print:hidden">{{ $this->paginatedReport->links() }}</div>
</div>
