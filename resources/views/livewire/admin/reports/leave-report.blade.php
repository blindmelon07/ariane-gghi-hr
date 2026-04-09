<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">Leave Report</span>
    </nav>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-100 rounded-lg"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75"/></svg></div>
            <div><p class="text-xs text-gray-500 uppercase">VL Used ({{ $year }})</p><p class="text-xl font-bold text-blue-600">{{ $this->summaryCards['vl_used'] }} days</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-red-100 rounded-lg"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg></div>
            <div><p class="text-xs text-gray-500 uppercase">SL Used ({{ $year }})</p><p class="text-xl font-bold text-red-600">{{ $this->summaryCards['sl_used'] }} days</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div class="p-3 bg-orange-100 rounded-lg"><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg></div>
            <div><p class="text-xs text-gray-500 uppercase">EL Used ({{ $year }})</p><p class="text-xl font-bold text-orange-600">{{ $this->summaryCards['el_used'] }} days</p></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Year</label>
                <select wire:model.live="year" class="rounded-lg border-gray-300 text-sm">
                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Leave Type</label>
                <select wire:model.live="leaveTypeId" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All</option>
                    @foreach ($this->leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                    @endforeach
                </select>
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
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select wire:model.live="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="flex gap-2 ml-auto">
                <button wire:click="exportExcel" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
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
        <div wire:loading.delay class="px-6 py-2 bg-indigo-50 text-indigo-600 text-xs font-medium print:hidden">Loading...</div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leave Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Days</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filed On</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($this->reportData as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $req->employee->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $req->leaveType->name ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $req->start_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $req->end_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-700 font-mono">{{ $req->total_days }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $c = match($req->status) {
                                    'approved' => 'green', 'pending' => 'yellow', 'rejected' => 'red', 'cancelled' => 'gray', default => 'gray'
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $req->approver?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $req->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">No leave requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 print:hidden">{{ $this->reportData->links() }}</div>
</div>
