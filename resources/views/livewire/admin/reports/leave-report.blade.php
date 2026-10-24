<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600 dark:text-indigo-400">Dashboard</a>
        <x-phosphor-caret-right-bold class="w-4 h-4" />
        <span class="text-gray-700 dark:text-gray-200 font-medium">Leave Report</span>
    </nav>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg"><x-phosphor-calendar-blank class="w-5 h-5 text-blue-600 dark:text-blue-400" /></div>
            <div><p class="text-xs text-gray-500 dark:text-gray-400 uppercase">VL Used ({{ $year }})</p><p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $this->summaryCards['vl_used'] }} days</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg"><x-phosphor-warning-circle class="w-5 h-5 text-red-600 dark:text-red-400" /></div>
            <div><p class="text-xs text-gray-500 dark:text-gray-400 uppercase">SL Used ({{ $year }})</p><p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $this->summaryCards['sl_used'] }} days</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg"><x-phosphor-warning class="w-5 h-5 text-orange-600 dark:text-orange-400" /></div>
            <div><p class="text-xs text-gray-500 dark:text-gray-400 uppercase">EL Used ({{ $year }})</p><p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $this->summaryCards['el_used'] }} days</p></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-auto">
                <label for="year" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Year</label>
                <select id="year" wire:model.live="year" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="leaveTypeId" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Leave Type</label>
                <select id="leaveTypeId" wire:model.live="leaveTypeId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    @foreach ($this->leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="department" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Department</label>
                <select id="department" wire:model.live="department" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="status" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select id="status" wire:model.live="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="flex gap-2 w-full sm:w-auto sm:ml-auto">
                <button wire:click="exportExcel" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <x-phosphor-download-simple-bold class="w-4 h-4" />
                    Excel
                </button>
                <button onclick="window.print()" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <x-phosphor-printer-bold class="w-4 h-4" />
                    Print
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden print:shadow-none">
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 text-xs font-medium print:hidden">Loading...</div>
        {{-- Cards (mobile) --}}
        <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-700 print:hidden">
            @forelse ($this->reportData as $req)
                @php
                    $c = match($req->status) {
                        'approved' => 'green', 'pending' => 'yellow', 'rejected' => 'red', 'cancelled' => 'gray', default => 'gray'
                    };
                @endphp
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $req->employee->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $req->leaveType->name ?? '' }}</p>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">{{ ucfirst($req->status) }}</span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $req->start_date->format('M d, Y') }} – {{ $req->end_date->format('M d, Y') }} · {{ $req->total_days }}d</p>
                    <div class="flex items-center justify-between mt-1 text-xs text-gray-400 dark:text-gray-500">
                        <span>Approved by: {{ $req->approver?->name ?? '—' }}</span>
                        <span>Filed {{ $req->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No leave requests found.</div>
            @endforelse
        </div>

        <div class="hidden lg:block overflow-x-auto print:block">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Leave Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Start</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">End</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Days</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Approved By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Filed On</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->reportData as $req)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $req->employee->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->leaveType->name ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->start_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->end_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200 font-mono">{{ $req->total_days }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $c = match($req->status) {
                                    'approved' => 'green', 'pending' => 'yellow', 'rejected' => 'red', 'cancelled' => 'gray', default => 'gray'
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->approver?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $req->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No leave requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="mt-4 print:hidden">{{ $this->reportData->links() }}</div>
</div>
